<?php

namespace Encrypt\Infrastructure\Ui\Html\Template;

class HomeScreenTemplate
{
    private const FORM_HTML_TEMPLATE = 'form.html';
    private const HTML_DELETE_BOTOM = '<a href="%s" type="button" class="btn btn-sm btn-danger">Delete</a>';
    private const HTML_DOWNLOAD_BUTTOM = '<li class="list-group-item d-flex justify-content-between align-items-center">
        <a href="#" class="">%s</a> 
        <a href="#" data-gpgfile="%s" data-file="%s" data-domain="%s" type="button" class="download btn btn-sm btn-success">Download</a>
        %s
        </li>';
    private const ADMIN_PANEL = '<div style="text-align: left">
        <small>
          <form method="post">
          <div><code>echo \'%s\' | gpg --clear-sign --armor</code></div> <hr />
          <div class="input-group mb-2">              
              <textarea rows="1" class="form-control" type="password" name="password" placeholder="Use and paste: echo \'%s\' | gpg --clear-sign --armor"></textarea>
              <button class="btn btn-outline-primary">Make admin</button>
          </div>
          </form>
        </small>
      </div>';
    private const ADMIN_LOGIN_EXIT = '<div style="text-align: left">
            <small>You are Admin! <a href="%s?a=logout' . '">Exit</a></small>
        </div>';

    /** @var string */
    private $domain;
    /**
     * @var bool
     */
    private $isAdmin;

    public function __construct(string $domain, bool $isAdmin)
    {
        $this->domain = $domain;
        $this->isAdmin = $isAdmin;
    }

    public function html($filesEncrypt, array $pubKeys, string $accessAdmin): string
    {
        $templateForm = file_get_contents(self::FORM_HTML_TEMPLATE);

        if ($this->isAdmin) {
            $admin = sprintf(self::ADMIN_LOGIN_EXIT, HTTP_SCHEME . $this->domain . DS);
        }
        if (!$this->isAdmin) {
            $admin = sprintf(self::ADMIN_PANEL, $accessAdmin, $accessAdmin);
        }

        $keysList = array_map(
            function(array $pubKey) {
                return '<option value="' . $pubKey['file'] . '">' . $pubKey['name'] . '</option>';
            },
            $pubKeys
        );

        $filesList = '';
        foreach ($filesEncrypt as $file) {
            if (is_file($file)) {
                $delete = $this->delete(
                    HTTP_SCHEME . $this->domain . DS . '?a=delete&f=' . $file
                );

                if (!$this->isAdmin) {
                    $delete = null;
                }

                $filesList .= $this->downloadBottom($file, $delete);
            }
        }

        $keysList = array_merge(['<option>Select pub key to encrypt</option>'], $keysList);

        return sprintf($templateForm, $admin, implode('', $keysList), $filesList);
    }

    private function delete(string $deleteLink): string
    {
        return sprintf(self::HTML_DELETE_BOTOM, $deleteLink);
    }

    private function downloadBottom(string $file, ?string $delete): string
    {
        return sprintf(
            self::HTML_DOWNLOAD_BUTTOM,
            basename($file),
            str_replace('/tmp/upload/', 'uploads/', str_replace(' ', '%20', $file)),
            basename(str_replace([' ', GPG_EXTENSION], ['_', ''], $file)),
            HTTP_SCHEME . $this->domain,
            $delete
        );
    }
}