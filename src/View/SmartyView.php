<?php
/**
 * Smarty View
 *
 * @link https://github.com/mrred85/cakephp-smarty-view
 * @copyright 2016 - present Victor Rosu. All rights reserved.
 * @license Licensed under the MIT License.
 */

namespace App\View;

use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\View\View;
use LogicException;
use Smarty;

/**
 * Your application’s default view class for Smarty
 * IMPORTANT: All templates files must have ".tpl" extension
 *
 * ### Smarty (configure values)
 * - error_reporting
 * - force_compile: bool
 * - caching: bool
 * - caching_time: int (seconds)
 * - compile_check: bool
 *
 * @package App\View
 * @use Smarty
 */
class SmartyView extends View
{
    /**
     * @var null|Smarty
     */
    protected $Smarty = null;

    /**
     * Constructor
     *
     * @param \Cake\Http\ServerRequest|null $request Request instance.
     * @param \Cake\Http\Response|null $response Response instance.
     * @param \Cake\Event\EventManager|null $eventManager Event manager instance.
     * @param array $viewOptions View options. See View::$_passedVars for list of
     *   options which get set as class properties.
     */
    public function __construct(
        ServerRequest $request = null,
        Response $response = null,
        EventManager $eventManager = null,
        array $viewOptions = []
    ) {
        $this->_ext = '.tpl';

        $cfgSmarty = Configure::read('Smarty');
        if (!$cfgSmarty) {
            throw new LogicException('The Smarty configure variable not present in "app.php" config file.');
        }
        $this->Smarty = new Smarty;

        if (!file_exists(CACHE . 'smarty')) {
            mkdir(CACHE . 'smarty', 0777);
        }
        $this->Smarty->debugging = Configure::read('debug');
        $this->Smarty->error_reporting = $cfgSmarty['error_reporting'];
        $this->Smarty->force_compile = $cfgSmarty['force_compile'];
        if (isset($cfgSmarty['caching'])) {
            $this->Smarty->setCaching($cfgSmarty['caching']);
        }
        if (isset($cfgSmarty['caching_time'])) {
            $this->Smarty->setCacheLifetime($cfgSmarty['caching_time']);
        }
        if (isset($cfgSmarty['compile_check'])) {
            $this->Smarty->setCompileCheck($cfgSmarty['compile_check']);
        }
        $this->Smarty->setCompileDir(CACHE . 'views');
        $this->Smarty->setCacheDir(CACHE . 'smarty');
        $this->Smarty->setConfigDir(CONFIG);
        $this->Smarty->setTemplateDir(APP . 'View');

        parent::__construct($request, $response, $eventManager, $viewOptions);
    }

    /**
     * Initialization hook method.
     *
     * Properties like $helpers etc. cannot be initialized statically in your custom
     * view class as they are overwritten by values from controller in constructor.
     * So this method allows you to manipulate them as required after view instance
     * is constructed.
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();

        $this->loadHelper('Breadcrumbs');
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

    /**
     * Renders and returns output for given template filename with its
     * array of data. Handles parent/extended templates.
     *
     * @param string $viewFile Filename of the view
     * @param array $data Data to include in rendered view. If empty the current
     *   View::$viewVars will be used.
     * @return string Rendered output
     * @throws \LogicException When a block is left open.
     * @throws \Exception
     * @triggers View.beforeRenderFile $this, [$viewFile]
     * @triggers View.afterRenderFile $this, [$viewFile, $content]
     */
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
