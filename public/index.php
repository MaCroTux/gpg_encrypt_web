<?php


include '../vendor/autoload.php';

use Encrypt\Application\UseCase\AdminAccessUseCase;
use Encrypt\Application\UseCase\DeleteGpgFileUseCase;
use Encrypt\Application\UseCase\EncryptFileUseCase;
use Encrypt\Application\UseCase\HomeScreenUseCase;
use Encrypt\Domain\Admin\GenerateAdminAccessService;
use Encrypt\Infrastructure\Encrypt\FileEncryptService;
use Encrypt\Infrastructure\InSession\Repository\SessionAccessAdminRepository;
use Encrypt\Infrastructure\Persistence\Repository\FileGpgRepository;
use Encrypt\Infrastructure\Persistence\Repository\FilePubKeysRepository;
use Encrypt\Infrastructure\Ui\Html\Template\HomeScreenTemplate;
use Encrypt\Infrastructure\Verify\FileVerifySignService;

session_start();

const HTTP_SCHEME = 'http://';
const DS = '/';
const UPLOAD_PATH = '/tmp/upload';

const DELETE_ACTION = 'delete';
const LOGOUT_ACTION = 'logout';

const DOMAIN_ENV = 'domain';

const GPG_EXTENSION = '.gpg';

putenv("GNUPGHOME=/tmp");
$domain = getenv(DOMAIN_ENV);
$isAdmin = $_SESSION['admin'] === 1;

$fileGpgRepository = new FileGpgRepository();
$sessionAccessAdmin = new SessionAccessAdminRepository($_SESSION);
$filePubKeysRepository = new FilePubKeysRepository();
$fileVerifySignService = new FileVerifySignService();
$fileEncryptService = new FileEncryptService($filePubKeysRepository, $domain);


$passwordInput = $_POST['password'] ?? null;
$action = $_GET['a'] ?? null;
$file = $_GET['f'] ?? null;

$fileTmp = $_FILES['fileToUpload'];
$fileError = $fileTmp['error'];

if ($fileError > 0) {
    die('File error');
}

$idPubKey = $_POST['pub_key'] ?? false;
$pubKeyAdmin = file_get_contents('../keys/YubiKey-1B5A649317D1D740D76797685A726ABCF3368202.pub');

$accessAdmin = (new GenerateAdminAccessService())->__invoke();

// ----------------------------  MAKE ADMIN WITH SIGNATURE
$adminAccessUseCase = new AdminAccessUseCase(
    $fileVerifySignService,
    $sessionAccessAdmin,
    $domain,
    $pubKeyAdmin
);
if (!empty($passwordInput) && $adminAccessUseCase->__invoke($passwordInput)) {
    $_SESSION['admin'] = 1;
    header('Location: ' . HTTP_SCHEME . $domain);
    die();
} else {
    $_SESSION['ADMIN_ACCESS_PASS'] = $accessAdmin;
}

// ----------------------------  DELETE FILE
if ($action === DELETE_ACTION) {
    try {
        $deleteGpgFileUseCase = new DeleteGpgFileUseCase($fileGpgRepository, $isAdmin);
        $deleteGpgFileUseCase->__invoke($file);
    } catch (Exception $e) {
        die($e->getMessage() . ' <a href="' . HTTP_SCHEME . $domain . '">Back</a>');
    }

    header('Location: ' . HTTP_SCHEME . $domain);
    die();
}

// ----------------------------  LOGOUT ACTION

if ($action === LOGOUT_ACTION) {
    $sessionAccessAdmin->deleteAdminSession();
    header('Location: ' . HTTP_SCHEME . $domain);
    die();
}

// ----------------------------  FILES
if (empty($_FILES)) {
    $homeScreenUseCase = new HomeScreenUseCase(
        $fileGpgRepository,
        $filePubKeysRepository,
        new HomeScreenTemplate($domain, $isAdmin),
        getenv(DOMAIN_ENV)
    );

    echo $homeScreenUseCase->__invoke($isAdmin, $accessAdmin);

    die();
}

// ----------------------------  ENCRYPT FILE
try {
    $encryptFileUseCase = new EncryptFileUseCase($fileEncryptService, $fileGpgRepository);
    $encryptFileUseCase->__invoke($idPubKey, $fileTmp['tmp_name'], $fileTmp['name'], $fileTmp['type']);
} catch (Exception $e) {
    die($e->getMessage() . ' <a href="' . HTTP_SCHEME . $domain . '">Back</a>');
}

header('Location: ' . HTTP_SCHEME . $domain);
