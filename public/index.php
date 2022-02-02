<?php


include '../vendor/autoload.php';

use Encrypt\Application\UseCase\AdminAccessUseCase;
use Encrypt\Application\UseCase\HomeScreenUseCase;
use Encrypt\Domain\Admin\GenerateAdminAccessService;
use Encrypt\Infrastructure\Encrypt\FileEncryptService;
use Encrypt\Infrastructure\InSession\Repository\SessionAccessAdminRepository;
use Encrypt\Infrastructure\Persistence\Repository\FileGPGSearchRepository;
use Encrypt\Infrastructure\Persistence\Repository\FilePubKeysRepository;
use Encrypt\Infrastructure\Ui\Html\Template\HomeScreenTemplate;

session_start();

const HTTP_SCHEME = 'http://';
const GNUPGHOME = "GNUPGHOME=/tmp";
const DS = '/';
const UPLOAD_PATH = '/tmp/upload';

const DELETE_ACTION = 'delete';
const LOGOUT_ACTION = 'logout';

const DOMAIN_ENV = 'domain';

const GPG_EXTENSION = '.gpg';

$fileGpgRepository = new FileGPGSearchRepository();
$sessionAccessAdmin = new SessionAccessAdminRepository($_SESSION);
$filePubKeysRepository = new FilePubKeysRepository();

putenv(GNUPGHOME);
$domain = getenv(DOMAIN_ENV);
$isAdmin = $_SESSION['admin'] === 1;

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
$adminAccessUseCase = new AdminAccessUseCase($sessionAccessAdmin, $domain, $pubKeyAdmin);
if (!empty($passwordInput) && $adminAccessUseCase->__invoke($passwordInput)) {
    $_SESSION['admin'] = 1;
    header('Location: ' . HTTP_SCHEME . $domain);
    die();
} else {
    $_SESSION['ADMIN_ACCESS_PASS'] = $accessAdmin;
}


// ----------------------------  DELETE FILE
if ($action === DELETE_ACTION && strpos($file, UPLOAD_PATH . '/') >= 0 && is_file($file)) {
    if (!$isAdmin) {
        header('Location: ' . HTTP_SCHEME . $domain);
        die();
    }

    @unlink($file);
    header('Location: ' . HTTP_SCHEME . $domain);
    die();
}

// ----------------------------  LOGOUT ACTION

if ($action === LOGOUT_ACTION) {
    $sessionAccessAdmin->deleteAdminSession();
    header('Location: ' . HTTP_SCHEME . $domain);
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
$fileEncryptService = new FileEncryptService($filePubKeysRepository, $domain);

try {
    $encryptFileContent = $fileEncryptService->__invoke($fileTmp['tmp_name'], $idPubKey);
    $fileGpgRepository->saveGpgFile($fileTmp['name'], $fileTmp['type'], $encryptFileContent);

    if (null === $encryptFileContent) {
        echo 'Decrypt: ERROR <br/><br/> <a href="' . HTTP_SCHEME . $domain . '">Back</a>';
        die();
    }
} catch (\Exception $e) {
    die($e->getMessage() . ' <a href="' . HTTP_SCHEME . $domain . '">Back</a>');
}

header('Location: ' . HTTP_SCHEME . $domain);

// ----------------------------  FUNCTIONS

function verify(string $signature, string $pubkey = null, string $signedText): bool
{
    $res = gnupg_init();

    gnupg_seterrormode($res, GNUPG_ERROR_WARNING);

    $public = gnupg_import($res, $pubkey);
    $publicFingerprint = $public['fingerprint'] ?? null;

    $publicsInfo = gnupg_keyinfo($res, $publicFingerprint);
    $publicInfo = array_shift($publicsInfo);

    $publicSigns = array_filter($publicInfo['subkeys'] ?? [], static function (array $keys) {
        return $keys['can_sign'] === true;
    });

    $publicSign = array_shift($publicSigns);
    $publicFingerprint = $publicSign['fingerprint'];

    $response = gnupg_verify($res, $signature, false);

    $verify = array_shift($response);
    $signatureFingerprint = $verify['fingerprint'];

    return $publicFingerprint === $signatureFingerprint
        && strpos($signature, $signedText) !== false;
}
