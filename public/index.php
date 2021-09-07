<?php

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

const PUB_ID = "8308F0546637C1F37991936034E3BE38E6E11689";
const PUB_KEY = "-----BEGIN PGP PUBLIC KEY BLOCK-----
mFIEAAAAABMIKoZIzj0DAQcCAwRcG7waIiC0F9dK9p5jGF3NyN5+9JpPGHr2BoZI
RbYjntRtr3RgAbxtFybfzY+uPanwyuVzCZhHSN4e/bEOIOEPtCtGcmFuY2lzY28g
SmF2aWVyIEZlcmlhIDxtYWNyb3R1eEBnbWFpbC5jb20+iIAEExMIABwFAgAAAAAC
CwkCGwMEFQgJCgQWAgMBAheAAh4BABYJEBNsrPFuK7G5CxpUUkVaT1ItR1BHD+YB
AMWDPuHb7dlfKfIfQHdpEd4WC9yTcCPJHFQVoUv5y2E0AP9zKzrMy1H9mOZ3XclR
6p6AiJapJ4ixaiTvNZATGLvKBbhWBAAAAAASCCqGSM49AwEHAgME1TLSdikB5yI8
wRgIHYVsn0V1qd3LVhJ1Uhi6ji2rgWs/nUke4IsmL848joOtFcd7tWb0WYs+d0XF
OPIZhhRsYAMBCAeIbQQYEwgACQUCAAAAAAIbDAAWCRATbKzxbiuxuQsaVFJFWk9S
LUdQR0a7AQDp8jECdqr6CI3I8eMxJpqvf1Ed+e4cVswfFY8SxzEp/wD+M9aMNjQU
WQgqKPzU0G7Lkv4MngJ7V/95f4Mnfa/uiFM=
=7cB1
-----END PGP PUBLIC KEY BLOCK-----";

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

$uploadDir = UPLOAD_PATH . DS . str_replace(DS, '_',$fileTmp['type']);
$uploadFile = $uploadDir . DS . $fileTmp['name'] . GPG_EXTENSION;

// ----------------------------  MAKE ADMIN WITH SIGNATURE
if (
    !empty($passwordInput) &&
    verify(PUB_ID, ADMIN_TEXT_TO_SIGN, $passwordInput, PUB_KEY)
) {
    $_SESSION['admin'] = 1;
    header('Location: ' . HTTP_SCHEME . $domain);
    die();
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

// ----------------------------  UPLOAD FILE

if (empty($_FILES)) {
    $form = file_get_contents(FORM_HTML_TEMPLATE);
    $storage = '';
    $storage = shell_exec('find ' . UPLOAD_PATH . '/');
    $storage = str_replace(PHP_EOL, ";", $storage);
    $tmp = '';
    foreach (explode(';', $storage) as $file) {
        if (is_file($file)) {
            $delete = sprintf(
                '<a href="%s" type="button" class="btn btn-sm btn-danger">Delete</a>',
                HTTP_SCHEME . $domain . DS . '?a=delete&f=' . $file
            );

            if (!$isAdmin) {
                $delete = null;
            }

            $tmp .= sprintf('<li class="list-group-item d-flex justify-content-between align-items-center">
                <a href="#" class="">%s</a> 
                <a href="#" data-gpgfile="%s" data-file="%s" data-domain="%s" type="button" class="download btn btn-sm btn-success">Download</a>
                %s
                 </li>',
                basename($file),
                str_replace(' ', '%20', $file),
                basename(str_replace([' ', GPG_EXTENSION], ['_', ''], $file)),
                HTTP_SCHEME . $domain,
                $delete
            );

        }
    }

    $admin = '<div style="text-align: left">
        <small>
          <form method="post">
          <div class="input-group mb-2">
              <textarea rows="1" class="form-control" type="password" name="password" placeholder="Use and paste: echo \'Get admin access\' | gpg --detach-sign --armor"></textarea>
              <button class="btn btn-outline-primary">Make admin</button>
          </div>
          </form>
        </small>
      </div>';

    if ($isAdmin) {
        $admin = '<div style="text-align: left">
            <small>You are Admin! <a href="' . HTTP_SCHEME . $domain . DS . '?a=logout' . '">Exit</a></small>
        </div>';
    }

    die(sprintf($form, $admin, $tmp));
}

// ----------------------------  ENCRYPT FILE

$rawFile = file_get_contents($fileTmp['tmp_name']);

$enc = (null);
$enc = encrypt(PUB_ID, $rawFile, PUB_KEY);
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

function verify(string $pubId, string $text, string $signature, string $pubkey = null): bool
{
    $res = gnupg_init();

    if (null !== $pubkey) {
        $rtv = gnupg_import($res, $pubkey);
        if (false === $rtv) {
            return false;
        }
    }

    $rtv = gnupg_addencryptkey($res, $pubId);
    if (false === $rtv) {
        return false;
    }

    $res = gnupg_verify(
        $res,
        $signature,
        false,
        $text
    );

    return $res !== false;
}