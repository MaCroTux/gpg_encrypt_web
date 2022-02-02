<?php


include '../vendor/autoload.php';

use Encrypt\Application\UseCase\AdminAccessUseCase;
use Encrypt\Application\UseCase\HomeScreenUseCase;
use Encrypt\Domain\Admin\GenerateAdminAccessService;
use Encrypt\Infrastructure\InSession\Repository\SessionAccessAdminRepository;

session_start();

const HTTP_SCHEME = 'http://';
const GNUPGHOME = "GNUPGHOME=/tmp";
const DS = '/';
const UPLOAD_PATH = '/tmp/upload';

const DELETE_ACTION = 'delete';
const LOGOUT_ACTION = 'logout';

const DOMAIN_ENV = 'domain';

const GPG_EXTENSION = '.gpg';

$sessionAccessAdmin = new SessionAccessAdminRepository($_SESSION);

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
$pubKeyId = null;
if (($idPubKey ?? false) && is_file('../keys/' . $idPubKey . '.pub')) {
    $pubKey = file_get_contents('../keys/' . $idPubKey . '.pub');
    [$name, $pubKeyId] = explode('-', $idPubKey);
}

$pubKeyAdmin = file_get_contents('../keys/YubiKey-1B5A649317D1D740D76797685A726ABCF3368202.pub');

$uploadDir = UPLOAD_PATH . DS . str_replace(DS, '_',$fileTmp['type']);
$uploadFile = $uploadDir . DS . $fileTmp['name'] . GPG_EXTENSION;

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
    $_SESSION['admin'] = null;
    header('Location: ' . HTTP_SCHEME . $domain);
}

// ----------------------------  FILES
if (empty($_FILES)) {
    die((new HomeScreenUseCase(
        getenv(DOMAIN_ENV),
        $isAdmin,
        $accessAdmin
    ))->__invoke());
}

// ----------------------------  ENCRYPT FILE
$rawFile = file_get_contents($fileTmp['tmp_name']);

if (null === $pubKey) {
    die('Pub key invalid <a href="' . HTTP_SCHEME . $domain . '">Back</a>');
}

$enc = (null);
$enc = encrypt($pubKeyId, $rawFile, $pubKey);
if (null === $enc) {
    echo 'Decrypt: ERROR <br/><br/> <a href="' . HTTP_SCHEME . $domain . '">Back</a>';
    die();
}

if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }

// ----------------------------  SAVE ENCRYPT FILE

file_put_contents($uploadFile, $enc);

header('Location: ' . HTTP_SCHEME . $domain);

// ----------------------------  FUNCTIONS

function encrypt(string $pubId, string $dataToEncrypt, string $pubkey = null): ?string
{
    $res = gnupg_init();

    if (null !== $pubkey) {
        $rtv = gnupg_import($res, $pubkey);
        if (false === $rtv) {
            return null;
        }
    }

    $rtv = gnupg_addencryptkey($res, $pubId);
    if (false === $rtv) {
        return null;
    }

    return gnupg_encrypt($res, $dataToEncrypt);
}

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
