<?php

namespace LeKoala\CmsActions\Test;

use SilverStripe\Forms\Form;
use SilverStripe\Security\Member;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use LeKoala\CmsActions\ActionsGridFieldItemRequest;
use LeKoala\CmsActions\CustomLink;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;

/**
 * Tests for Cms Actions module
 */
class CmsActionsTest extends SapphireTest
{
    /**
     * Defines the fixture file to use for this test class
     * @var string
     */
    protected static $fixture_file = 'CmsActionsTest.yml';

    protected static $extra_dataobjects = array(
        Test_CmsActionsModel::class,
        Test_ActionsPage::class,
    );

    public function setUp()
    {
        parent::setUp();
        $controller = Controller::curr();
        $controller->config()->set('url_segment', 'test_controller');
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @return Test_ActionsPage
     */
    public function getTestPage()
    {
        return $this->objFromFixture(Test_ActionsPage::class, 'demo');
    }

    /**
     * @return Test_CmsActionsModel
     */
    public function getTestModel()
    {
        return $this->objFromFixture(Test_CmsActionsModel::class, 'demo');
    }

    /**
     * @return Member
     */
    public function getAdminMember()
    {
        return $this->objFromFixture(Member::class, 'admin');
    }

    /**
     * @return Form
     */
    public function getMemberForm()
    {
        $controller = Controller::curr();
        $form = new Form($controller);

        $record = $this->getAdminMember();

        $list = Member::get();
        $gridField = new GridField('testGridfield', null, $list);
        $detailForm = new GridFieldDetailForm('testDetailForm');
        $GridFieldDetailForm = new GridFieldDetailForm_ItemRequest($gridField, $detailForm, $record, $controller, 'testPopup');
        $form = $GridFieldDetailForm->ItemEditForm();
        $form->loadDataFrom($record);

        return $form;
    }

    /**
     * @param Controller $controller
     * @return Form
     */
    public function getTestForm($controller = null)
    {
        if (!$controller) {
            $controller = Controller::curr();
        }

        $record = $this->getTestModel();

        $list = Test_CmsActionsModel::get();
        $gridField = new GridField('testGridfield', null, $list);
        $detailForm = new GridFieldDetailForm('testDetailForm');
        $GridFieldDetailForm = new GridFieldDetailForm_ItemRequest($gridField, $detailForm, $record, $controller, 'testPopup');
        $form = $GridFieldDetailForm->ItemEditForm();
        $form->loadDataFrom($record);

        return $form;
    }

    public function testCustomDeleteTitle()
    {
        $form = $this->getTestForm();
        $record = $form->getRecord();

        $delete = $form->Actions()->fieldByName("action_doDelete");
        $this->assertEquals($delete->Title(), $record->getDeleteButtonTitle());
    }

    public function testHasSaveAndClose()
    {
        $form = $this->getTestForm();

        $doSaveAndClose = $form->Actions()->fieldByName("action_doSaveAndClose");
        // It can be nested in MajorActions, then we need to use dot notation
        if (!$doSaveAndClose) {
            $doSaveAndClose = $form->Actions()->fieldByName("MajorActions.action_doSaveAndClose");
        }
        $this->assertNotEmpty($doSaveAndClose);
    }

    public function testHasDefaultTitle()
    {
        $customLink = new CustomLink('doTest');
        $this->assertEquals('Do test', $customLink->getTitle());
    }

    public function testConfirmationMessage()
    {
        $customLink = new CustomLink('doTest');
        $customLink->setConfirmation(true);
        $this->assertContains('sure', $customLink->getConfirmation());
    }

    public function testGridFieldAction()
    {
        $form = $this->getTestForm();
        $action = new Test_GridFieldAction;

        $record = $this->getTestModel();
        $list = Test_CmsActionsModel::get();
        $gridField = new GridField('testGridfield', null, $list);
        $actionName = 'test';
        $arguments = ['ID' => $record->ID];
        $data = [];

        $result = $action->doHandle($gridField, $actionName, $arguments, $data);

        $this->assertEquals($actionName, $action->performedActionName);
        $this->assertEquals($arguments, $action->performedArguments);
        $this->assertEquals($data, $action->performedData);
    }

    public function testLeftAndMain()
    {
        $leftAndMain = LeftAndMain::create();
        $form = $this->getTestForm($leftAndMain);

        $page = $this->getTestPage();
        // otherwise getRecord complains
        $leftAndMain->record = $page;
        $result = $leftAndMain->doCustomAction(
            [
                'action_doCustomAction' => [
                    'testAction' => 1
                ],
                'ID' => $page->ID,
                'ClassName' => $page->ClassName
            ],
            $form
        );

        $this->assertEquals($page->testAction(), $form->getMessage());
    }
}
