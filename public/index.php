<?php


include '../vendor/autoload.php';

use Encrypt\Application\UseCase\HomeScreenUseCase;

session_start();

const HTTP_SCHEME = 'http://';
const GNUPGHOME = "GNUPGHOME=/tmp";
const DS = '/';
const UPLOAD_PATH = '/tmp/upload';

const DELETE_ACTION = 'delete';
const LOGOUT_ACTION = 'logout';

const DOMAIN_ENV = 'domain';

const FORM_HTML_TEMPLATE = 'form.html';
const GPG_EXTENSION = '.gpg';
const ADMIN_TEXT_TO_SIGN = 'Get admin access';

$pubKeys = glob('../keys/*.pub');

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

$uploadDir = UPLOAD_PATH . DS . str_replace(DS, '_',$fileTmp['type']);
$uploadFile = $uploadDir . DS . $fileTmp['name'] . GPG_EXTENSION;

// ----------------------------  MAKE ADMIN WITH SIGNATURE
$accessAdmin = ADMIN_TEXT_TO_SIGN . ' ' . substr(hash('sha256', time()), 0 ,6);
if (
    !empty($passwordInput) &&
    verify($passwordInput, $pubKey, ADMIN_TEXT_TO_SIGN)
) {
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
    $form = file_get_contents(FORM_HTML_TEMPLATE);
    die((new HomeScreenUseCase(getenv(DOMAIN_ENV), $isAdmin))->__invoke());
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
