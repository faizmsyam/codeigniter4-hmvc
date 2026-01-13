<?php

/**
 * @author     Faiz Muhammad Syam, S.Kom, M.TI
 * @e-mail     faizmsyam@gmail.com
 * @license    FMS Signature
 */

namespace App\Core;

use Config\App;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class FMSController extends Controller
{
  public $helpers = ['fms'];

  /**
   * Request instance.
   *
   * @var \CodeIgniter\HTTP\IncomingRequest
   */
  protected $request;

  /**
   * Response instance.
   *
   * @var \CodeIgniter\HTTP\ResponseInterface
   */
  protected $response;

  /**
   * Session instance.
   *
   * @var \CodeIgniter\Session\Session
   */
  protected $session;

  /**
   * Services that can be used globally.
   */

  public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
  {
    parent::initController($request, $response, $logger);

    $this->request  = $request;
    $this->response = $response;
    $this->session  = session();

    $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    $this->response->setHeader('Pragma', 'no-cache');
    $this->response->setHeader('Expires', 'Tue, 01 Jan 2000 00:00:00 GMT');
    $this->response->setHeader('Last-Modified', gmdate("D, d M Y H:i:s") . " GMT");
    $this->response->setHeader('Strict-Transport-Security', 'max-age=31536000');
    $this->response->setHeader('Referrer-Policy', 'no-referrer-when-downgrade');
    $this->response->setHeader('X-Powered-By', APP_AUTHOR);
    $this->response->setHeader('X-Signature-By', APP_SIGNATURE);
  }

  /**
   * Return JSON response
   */
  protected function json($data = [], int $status = 200): ResponseInterface
  {
    return $this->response
      ->setStatusCode($status)
      ->setContentType('application/json')
      ->setJSON($data);
  }

  /**
   * Return Dynamic View
   */
  protected string $appName;
  protected string $titleTemplate;
  protected string $module = '';
  protected string $location = '';
  protected string $viewBasePath = '';
  protected string $assetBasePath = '';

  public function __construct()
  {
    $app = config(App::class);
    $this->appName = $app->appName;
    $this->titleTemplate = '%s - ' . $this->appName;

    $this->assetBasePath = 'assets/fms';

    $class = static::class;
    if (
      preg_match('#App\\\Modules\\\(.*?)\\\Controllers\\\(Frontend|Backend|Auth|Api)#', $class, $matches)
    ) {
      $this->module = $matches[1];
      $this->location = strtolower($matches[2]);
      $this->viewBasePath = APPPATH . "Modules/{$this->module}/Views/{$this->location}/";
    }

    if (
      preg_match('#App\\\\Modules\\\\(.*?)\\\\Controllers\\\\(Frontend|Backend|Auth|Api)#', $class, $matches)
    ) {
      $this->module = $matches[1];
      $this->location = strtolower($matches[2]);
      $this->viewBasePath = APPPATH . "Modules/{$this->module}/Views/{$this->location}/";
    } elseif (
      preg_match('#App\\\\Modules\\\\(.*?)\\\\Controllers#', $class, $matches)
    ) {

      $this->module = $matches[1];
      $this->location = '';
      $this->viewBasePath = APPPATH . "Modules/{$this->module}/Views/";
    } else {
      throw new \RuntimeException("Failed detectiion module context from class: $class");
    }
  }

  protected array $meta = [];
  /**
   * Set meta tag data
   */
  protected function fmsMeta(array $metaParam = []): void
  {
    $this->meta['title'] = isset($metaParam['title']) && !empty($metaParam['title']) ? sprintf($this->titleTemplate, $metaParam['title']) : $this->appName;
    $this->meta['description'] = isset($metaParam['description']) && !empty($metaParam['description']) ? $metaParam['description'] : APP_DESCRIPTION;
    $this->meta['keywords'] = isset($metaParam['keywords']) && !empty($metaParam['keywords']) ? $metaParam['keywords'] : '';
    $this->meta['image'] = isset($metaParam['image']) && !empty($metaParam['image']) ? $metaParam['image'] : base_url('favicon.ico');
    $this->meta['url'] = isset($metaParam['url']) && !empty($metaParam['url']) ? $metaParam['url'] : current_url();
    $this->meta['author'] = APP_AUTHOR;
    $this->meta['signature'] = APP_SIGNATURE;
  }

  /**
   * Get meta tag array
   */

  public function getMeta(): array
  {
    return $this->meta;
  }

  protected array $fmsLinks = [];
  protected array $fmsScripts = [];
  protected array $fmsBottomScripts = [];

  protected function fmsLink(string $href, string $rel = 'stylesheet', string $type = 'text/css'): void
  {
    $hrefLink = $href ? base_url($href) : '';
    $this->fmsLinks[] = "<link rel=\"{$rel}\" type=\"{$type}\" href=\"{$hrefLink}\">";
  }

  protected function fmsScript(string $src, string $type = 'text/javascript', bool $defer = false): void
  {
    $srcLink = $src ? base_url($src) : '';
    $deferAttr = $defer ? ' defer' : '';
    $this->fmsScripts[] = "<script type=\"{$type}\" src=\"{$srcLink}\"{$deferAttr}></script>";
  }
  
  protected function fmsBottomScript(string $src, string $type = 'text/javascript', bool $defer = false): void
  {
    $srcLink = $src ? base_url($src) : '';
    $deferAttr = $defer ? ' defer' : '';
    $this->fmsBottomScripts[] = "<script type=\"{$type}\" src=\"{$srcLink}\"{$deferAttr}></script>";
  }

  protected function getFmsLinks(): string
  {
    return implode($this->fmsLinks);
  }

  protected function getFmsScripts(): string
  {
    return implode($this->fmsScripts);
  }
  
  protected function getFmsBottomScripts(): string
  {
    return implode($this->fmsBottomScripts);
  }
  
  protected function fmsLayout(string $view, array $data = []): string
  {
    if (empty($this->meta)) {
      $this->fmsMeta();
    }

    if ($this->location === 'auth') $this->fmsInitializeAuth();
    if ($this->location === 'backend') $this->fmsInitializeBackend();

    $checkPath = APPPATH . "Modules/{$this->module}/Views/{$this->location}/{$view}.php";
    if (is_file($checkPath) && !empty($this->location)) {
      $viewPath = "{$this->location}/index";
      $viewContentPath = "App\\Modules\\{$this->module}\\Views\\{$this->location}\\{$view}";
    } else {
      $checkPath = APPPATH . "Modules/{$this->module}/Views/{$view}.php";
      if (is_file($checkPath)) {
        $viewPath = "index";
        $viewContentPath = "App\\Modules\\{$this->module}\\Views\\{$view}";
      } else {
        throw new \RuntimeException("View file not found: {$checkPath}");
      }
    }
    
    $data['meta'] = $this->getMeta();
    $data['content'] = view($viewContentPath, $data);

    if (!empty($this->fmsLinks)) $data['fmsLinks'] = $this->getFmsLinks();
    if (!empty($this->fmsScripts)) $data['fmsScripts'] = $this->getFmsScripts();
    if (!empty($this->fmsBottomScripts)) $data['fmsBottomScripts'] = $this->getFmsBottomScripts();

    return view($viewPath, $data);
  }

  protected function fmsView(string $view, array $data = []): string
  {
    $filePath = $this->viewBasePath . $view . '.php';

    if (!is_file($filePath)) {
      throw new \RuntimeException("View file not found: {$filePath}");
    }

    $data['meta'] = $this->getMeta();
    return $this->fmsLoadView($filePath, $data);
  }

  protected function fmsLoadView(string $file, array $data = []): string
  {
    extract($data);
    ob_start();
    include $file;
    return ob_get_clean();
  }

  function fmsInitializeAuth()
  {
    $this->fmsLink($this->assetBasePath.'/libs/bootstrap/css/bootstrap.min.css');
    $this->fmsLink($this->assetBasePath.'/css/styles.css');
    $this->fmsLink($this->assetBasePath.'/css/auth/styles.css');
    $this->fmsLink($this->assetBasePath.'/css/icons.css');

    $this->fmsScript($this->assetBasePath.'/libs/bootstrap/js/bootstrap.bundle.min.js');
    $this->fmsScript($this->assetBasePath.'/js/authentication-main.js');
    $this->fmsScript($this->assetBasePath.'/js/show-password.js');
  }

  function fmsInitializeBackend()
  {
    $this->fmsLink($this->assetBasePath.'/libs/bootstrap/css/bootstrap.min.css');
    $this->fmsLink($this->assetBasePath.'/css/styles.css');
    $this->fmsLink($this->assetBasePath.'/css/icons.css');
    $this->fmsLink($this->assetBasePath.'/libs/node-waves/waves.min.css');
    $this->fmsLink($this->assetBasePath.'/libs/simplebar/simplebar.min.css');
    $this->fmsLink($this->assetBasePath.'/libs/flatpickr/flatpickr.min.css');
    $this->fmsLink($this->assetBasePath.'/libs/@simonwep/pickr/themes/nano.min.css');
    $this->fmsLink($this->assetBasePath.'/libs/choices.js/public/assets/styles/choices.min.css');
    $this->fmsLink($this->assetBasePath.'/libs/@tarekraafat/autocomplete.js/css/autoComplete.css');
    $this->fmsLink($this->assetBasePath.'/css/backend/styles.css');

    $this->fmsScript($this->assetBasePath.'/libs/choices.js/public/assets/scripts/choices.min.js');
    $this->fmsScript($this->assetBasePath.'/js/main.js');
    $this->fmsBottomScript($this->assetBasePath.'/libs/@popperjs/core/umd/popper.min.js');
    $this->fmsBottomScript($this->assetBasePath.'/libs/bootstrap/js/bootstrap.bundle.min.js');
    $this->fmsBottomScript($this->assetBasePath.'/js/defaultmenu.min.js');
    $this->fmsBottomScript($this->assetBasePath.'/libs/node-waves/waves.min.js');
    $this->fmsBottomScript($this->assetBasePath.'/js/sticky.js');
    $this->fmsBottomScript($this->assetBasePath.'/libs/simplebar/simplebar.min.js');
    $this->fmsBottomScript($this->assetBasePath.'/js/simplebar.js');
    $this->fmsBottomScript($this->assetBasePath.'/libs/@tarekraafat/autocomplete.js/autoComplete.min.js');
    $this->fmsBottomScript($this->assetBasePath.'/libs/@simonwep/pickr/pickr.es5.min.js');
    $this->fmsBottomScript($this->assetBasePath.'/libs/flatpickr/flatpickr.min.js');
    $this->fmsBottomScript($this->assetBasePath.'/js/custom-switcher.min.js');
    $this->fmsBottomScript($this->assetBasePath.'/js/custom.js');
  }
}
