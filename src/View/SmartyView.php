<?php
/**
 * @name SmartyView
 * @author Victor Rosu
 * @copyright Red Px. All rights reserved.
 * @link http://www.redpx.ro/
 */

namespace App\View;

use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\View\View;
use Cake\Routing\Route;
use LogicException;
use Smarty;


/**
 * Application View
 * Your application’s default view class for Smarty
 * @important All templates files must have ".tpl" extension
 * 
 * Smarty (configure values)
 * - error_reporing
 * - force_compile
 * - caching
 * - compile_check
 */
class SmartyView extends View
{
    protected $Smarty = null;

    public function __construct(
        Request $request = null,
        Response $response = null,
        EventManager $eventManager = null,
        array $viewOptions = []
    ) {
        $this->_ext = '.tpl';
        $cfgSmarty = Configure::read('Smarty');
        if (!$cfgSmarty) {
            throw new LogicException('The Smarty configure variable not present in app.php config file.');
        }
        $this->Smarty = new Smarty;

        if (!file_exists(CACHE . 'smarty')) {
            mkdir(CACHE . 'smarty', 0777);
        }
        $this->Smarty->debugging = Configure::read('debug');
        $this->Smarty->error_reporting = $cfgSmarty['error_reporting'];
        $this->Smarty->force_compile = $cfgSmarty['force_compile'];
        $this->Smarty->setCaching($cfgSmarty['caching']);
        $this->Smarty->setCacheLifetime($cfgSmarty['caching_time']);
        $this->Smarty->setCompileCheck($cfgSmarty['compile_check']);
        $this->Smarty->setCompileDir(CACHE . 'views');
        $this->Smarty->setCacheDir(CACHE . 'smarty');
        $this->Smarty->setConfigDir(CONFIG);
        $this->Smarty->setTemplateDir(APP . 'View');
        
        parent::__construct($request, $response, $eventManager, $viewOptions);
    }

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading helpers.
     *
     * e.g. `$this->loadHelper('Html');`
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();

        $this->loadHelper('Flash');
        $this->loadHelper('Form');
        $this->loadHelper('Html');
        $this->loadHelper('Number');
        $this->loadHelper('Paginator');
        $this->loadHelper('Rss');
        $this->loadHelper('Session');
        $this->loadHelper('Text');
        $this->loadHelper('Time');
        $this->loadHelper('Url');
    }

    protected function _render($viewFile, $data = [])
    {
        $viewInfo = pathinfo($viewFile);
        if ($viewInfo['extension'] === 'ctp') {
            return parent::_render($viewFile, $data);
        }
        if (empty($data)) {
            $data = $this->viewVars;
        }
        $this->_current = $viewFile;
        $initialBlocks = count($this->Blocks->unclosed());

        $this->dispatchEvent('View.beforeRenderFile', [$viewFile]);

        // Smarty
        foreach ($data as $key => $value) {
            $this->Smarty->assign($key, $value);
        }
        $registry = $this->helpers();
        $helpers = $registry->normalizeArray($this->helpers);
        foreach ($helpers as $name => $properties) {
            list(, $class) = pluginSplit($properties['class']);
            $helpers = $this->helpers();
            $this->{$class} = $helpers->load($properties['class'], $properties['config']);
            $this->Smarty->assignByRef(ucfirst(strtolower($name)), $this->{$class});
        }
        $this->Smarty->assignByRef('this', $this);
        $content = $this->Smarty->fetch($viewFile);
        // END Smarty

        $afterEvent = $this->dispatchEvent('View.afterRenderFile', [$viewFile, $content]);
        if (isset($afterEvent->result)) {
            $content = $afterEvent->result;
        }

        if (isset($this->_parents[$viewFile])) {
            $this->_stack[] = $this->fetch('content');
            $this->assign('content', $content);

            $content = $this->_render($this->_parents[$viewFile]);
            $this->assign('content', array_pop($this->_stack));
        }

        $remainingBlocks = count($this->Blocks->unclosed());

        if ($initialBlocks !== $remainingBlocks) {
            throw new LogicException(sprintf(
                'The "%s" block was left open. Blocks are not allowed to cross files.',
                $this->Blocks->active()
            ));
        }
        return $content;
    }
}
