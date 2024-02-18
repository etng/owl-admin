<?php

namespace Slowlyo\OwlAdmin\Traits;

use Slowlyo\OwlAdmin\Admin;

trait ElementTrait
{
    /**
     * 基础页面
     *
     * @return \Slowlyo\OwlAdmin\Renderers\Page
     */
    protected function basePage()
    {
        return amis()->Page()->className('m:overflow-auto');
    }

    /**
     * 返回列表按钮
     *
     * @return \Slowlyo\OwlAdmin\Renderers\OtherAction
     */
    protected function backButton()
    {
        $path   = str_replace(Admin::config('admin.route.prefix'), '', request()->path());
        $script =
            sprintf('window.$owl.hasOwnProperty(\'closeTabByPath\') && window.$owl.closeTabByPath(\'%s\')', $path);

        return amis()->OtherAction()
            ->label(__('admin.back'))
            ->icon('fa-solid fa-chevron-left')
            ->level('primary')
            ->onClick('window.history.back();' . $script);
    }

    /**
     * 批量删除按钮
     *
     * @return \Slowlyo\OwlAdmin\Renderers\AjaxAction
     */
    protected function bulkDeleteButton()
    {
        return amis()->AjaxAction()
            ->api($this->getBulkDeletePath())
            ->icon('fa-solid fa-trash-can')
            ->label(__('admin.delete'))
            ->confirmText(__('admin.confirm_delete'));
    }

    /**
     * 新增按钮
     *
     * @param bool   $dialog
     * @param string $dialogSize
     *
     * @return \Slowlyo\OwlAdmin\Renderers\DialogAction|\Slowlyo\OwlAdmin\Renderers\LinkAction
     */
    protected function createButton(bool $dialog = false, string $dialogSize = '')
    {
        if ($dialog) {
            $form = $this->form(false)->api($this->getStorePath())->onEvent([]);

            $button = amis()->DialogAction()->dialog(
                amis()->Dialog()->title(__('admin.create'))->body($form)->size($dialogSize)
            );
        } else {
            $button = amis()->LinkAction()->link($this->getCreatePath());
        }

        return $button->label(__('admin.create'))->icon('fa fa-add')->level('primary');
    }

    /**
     * 行编辑按钮
     *
     * @param bool   $dialog
     * @param string $dialogSize
     *
     * @return \Slowlyo\OwlAdmin\Renderers\DialogAction|\Slowlyo\OwlAdmin\Renderers\LinkAction
     */
    protected function rowEditButton(bool $dialog = false, string $dialogSize = '')
    {
        if ($dialog) {
            $form = $this->form(true)
                ->api($this->getUpdatePath())
                ->initApi($this->getEditGetDataPath())
                ->redirect('')
                ->onEvent([]);

            $button = amis()->DialogAction()->dialog(
                amis()->Dialog()->title(__('admin.edit'))->body($form)->size($dialogSize)
            );
        } else {
            $button = amis()->LinkAction()->link($this->getEditPath());
        }

        return $button->label(__('admin.edit'))->icon('fa-regular fa-pen-to-square')->level('link');
    }

    /**
     * 行详情按钮
     *
     * @param bool   $dialog
     * @param string $dialogSize
     *
     * @return \Slowlyo\OwlAdmin\Renderers\DialogAction|\Slowlyo\OwlAdmin\Renderers\LinkAction
     */
    protected function rowShowButton(bool $dialog = false, string $dialogSize = '')
    {
        if ($dialog) {
            $button = amis()->DialogAction()->dialog(
                amis()->Dialog()->title(__('admin.show'))->body($this->detail('$id'))->size($dialogSize)
            );
        } else {
            $button = amis()->LinkAction()->link($this->getShowPath());
        }

        return $button->label(__('admin.show'))->icon('fa-regular fa-eye')->level('link');
    }

    /**
     * 行删除按钮
     *
     * @return \Slowlyo\OwlAdmin\Renderers\AjaxAction
     */
    protected function rowDeleteButton()
    {
        return amis()->AjaxAction()
            ->label(__('admin.delete'))
            ->icon('fa-regular fa-trash-can')
            ->level('link')
            ->confirmText(__('admin.confirm_delete'))
            ->api($this->getDeletePath());
    }

    /**
     * 操作列
     *
     * @param bool   $dialog
     * @param string $dialogSize
     *
     * @return \Slowlyo\OwlAdmin\Renderers\Operation
     */
    protected function rowActions(bool|array $dialog = false, string $dialogSize = '')
    {
        if (is_array($dialog)) {
            return amis()->Operation()->label(__('admin.actions'))->buttons($dialog);
        }

        return amis()->Operation()->label(__('admin.actions'))->buttons([
            $this->rowShowButton($dialog, $dialogSize),
            $this->rowEditButton($dialog, $dialogSize),
            $this->rowDeleteButton(),
        ]);
    }

    /**
     * 基础筛选器
     *
     * @return \Slowlyo\OwlAdmin\Renderers\Form
     */
    protected function baseFilter()
    {
        return amis()->Form()
            ->panelClassName('base-filter')
            ->title('')
            ->actions([
                amis()->Button()->label(__('admin.reset'))->actionType('clear-and-submit'),
                amis('submit')->label(__('admin.search'))->level('primary'),
            ]);
    }

    /**
     * 基础筛选器 - 条件构造器
     *
     * @return \Slowlyo\OwlAdmin\Renderers\ConditionBuilderControl
     */
    protected function baseFilterConditionBuilder()
    {
        return amis()->ConditionBuilderControl('filter_condition_builder');
    }

    /**
     * @return \Slowlyo\OwlAdmin\Renderers\CRUDTable
     */
    protected function baseCRUD()
    {
        $crud = amis()->CRUDTable()
            ->perPage(20)
            ->affixHeader(false)
            ->filterTogglable()
            ->filterDefaultVisible(false)
            ->api($this->getListGetDataPath())
            ->quickSaveApi($this->getQuickEditPath())
            ->quickSaveItemApi($this->getQuickEditItemPath())
            ->bulkActions([$this->bulkDeleteButton()])
            ->perPageAvailable([10, 20, 30, 50, 100, 200])
            ->footerToolbar(['switch-per-page', 'statistics', 'pagination'])
            ->headerToolbar([
                $this->createButton(),
                ...$this->baseHeaderToolBar(),
            ]);

        if (isset($this->service)) {
            $crud->set('primaryField', $this->service->primaryKey());
        }

        return $crud;
    }

    protected function baseHeaderToolBar()
    {
        return [
            'bulkActions',
            amis('reload')->align('right'),
            amis('filter-toggler')->align('right'),
        ];
    }

    /**
     * 基础表单
     *
     * @param bool $back
     *
     * @return \Slowlyo\OwlAdmin\Renderers\Form
     */
    protected function baseForm(bool $back = true)
    {
        $path = str_replace(Admin::config('admin.route.prefix'), '', request()->path());

        $form = amis()->Form()->panelClassName('px-48 m:px-0')->title(' ')->mode('horizontal');

        if ($back) {
            $form->onEvent([
                'submitSucc' => [
                    'actions' => [
                        ['actionType' => 'custom', 'script' => 'window.history.back()'],
                        [
                            'actionType' => 'custom',
                            'script'     => sprintf('window.$owl.hasOwnProperty(\'closeTabByPath\') && window.$owl.closeTabByPath(\'%s\')', $path),
                        ],
                    ],
                ],
            ]);
        }

        return $form;
    }

    /**
     * @return \Slowlyo\OwlAdmin\Renderers\Form
     */
    protected function baseDetail()
    {
        return amis()->Form()
            ->panelClassName('px-48 m:px-0')
            ->title(' ')
            ->mode('horizontal')
            ->actions([])
            ->initApi($this->getShowGetDataPath());
    }

    /**
     * 基础列表
     *
     * @param $crud
     *
     * @return \Slowlyo\OwlAdmin\Renderers\Page
     */
    protected function baseList($crud)
    {
        return $this->basePage()->body($crud);
    }

    /**
     * 导出按钮
     *
     * @return \Slowlyo\OwlAdmin\Renderers\Alert|\Slowlyo\OwlAdmin\Renderers\DropdownButton
     */
    protected function exportAction($disableSelectedItem = false)
    {
        if (!class_exists('\Maatwebsite\Excel\Excel')) {
            return amis()
                ->Alert()
                ->level('warning')
                ->body(__('admin.export.please_install_laravel_excel'))
                ->showIcon()
                ->showCloseButton();
        }

        $primaryKey = $this->service->primaryKey();

        $downloadPath   = admin_url('_download_export', true);
        $exportPath     = $this->getExportPath();
        $pageNoData     = __('admin.export.page_no_data');
        $selectedNoData = __('admin.export.selected_rows_no_data');
        $event          = fn($script) => ['click' => ['actions' => [['actionType' => 'custom', 'script' => $script]]]];
        $doAction       = <<<JS
doAction([
    { actionType: "ajax", args: { api: { url: url.toString(), method: "get" } } },
    {
        actionType: "custom",
        expression: "\${event.data.responseResult.responseStatus === 0}",
        script: "window.open('{$downloadPath}?path='+event.data.responseResult.responseData.path)"
    }
])
JS;
        $buttons        = [
            amis()->VanillaAction()->label(__('admin.export.all'))->onEvent(
                $event(<<<JS
let data = event.data
let params = Object.keys(data).filter(key => key !== "page" && key !== "__super").reduce((obj, key) => {
    obj[key] = data[key];
    return obj;
}, {})
let url = new URL("{$exportPath}", window.location.origin)
Object.keys(params).forEach(key => url.searchParams.append(key, params[key]))
{$doAction}
JS

                )
            ),
            amis()->VanillaAction()->label(__('admin.export.page'))->onEvent(
                $event(<<<JS
let ids = event.data.items.map(item => item.{$primaryKey})
if(ids.length === 0) { return doAction({ actionType: "toast", args: { msgType: "warning", msg: "{$pageNoData}" } }) }
let url = new URL("{$exportPath}", window.location.origin)
url.searchParams.append("_ids", ids.join(","))
{$doAction}
JS
                )
            ),
        ];

        if (!$disableSelectedItem) {
            $buttons[] = amis()->VanillaAction()->label(__('admin.export.selected_rows'))->onEvent(
                $event(<<<JS
let ids = event.data.selectedItems.map(item => item.{$primaryKey})
if(ids.length === 0) { return doAction({ actionType: "toast", args: { msgType: "warning", msg: "{$selectedNoData}" } }) }
let url = new URL("{$exportPath}", window.location.origin)
url.searchParams.append("_ids", ids.join(","))
{$doAction}
JS
                )
            );
        }

        return amis()
            ->DropdownButton()
            ->label(__('admin.export.title'))
            ->set('icon', 'fa-solid fa-download')
            ->buttons($buttons)
            ->align('right')
            ->closeOnClick();
    }
}
