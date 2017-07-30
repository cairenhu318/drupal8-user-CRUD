<?php

namespace Drupal\employee\controller;

use Drupal\employee\EmployeeStorage;
use Drupal;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;

class EmployeeController extends ControllerBase{
  /*
   * The Form builder
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $form_builder;

  /*
   * Databse Connection 
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * Constructs the EmployeeController.
   *
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   *   The Form builder.
   */
  public function __construct(FormBuilder $form_builder, Connection $con){
      $this->form_builder = $form_builder;
      $this->db = $con;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container){
      return new static(
        $container->get('form_builder'),
        $container->get('database')
      );
  }
 /**
  * Lists all the employess
  */
  public function listEmployees() {
    $content = [];
    $content['search_form'] =
      $this->form_builder->getForm('Drupal\employee\forms\EmployeeSearchForm');
    $employee_table_form_instance =
      new Drupal\employee\forms\EmployeeTableForm($this->db);
    $content['table'] =
      $this->form_builder->getForm($employee_table_form_instance);
    $content['pager'] = [
      '#type' => 'pager',
    ];
    $content['#attached'] = ['library' => ['core/drupal.dialog.ajax']];
    return $content;
  }
  /**
   * To view an employee details
   */
  public function viewEmployee($employee, $js='nojs'){
    if($employee == 'invalid'){
      drupal_set_message(t('Invalid employee record'), 'error');
      return new RedirectResponse(Drupal::url('employee.list'));
    }
    $rows = [
        [
          ['data' => 'Id', 'header' => TRUE],
          $employee->id,
        ],
        [
          ['data' => 'Name', 'header' => TRUE],
          $employee->name,
        ],
        [
          ['data' => 'Email', 'header' => TRUE],
          $employee->email,
        ],
        [
          ['data' => 'Department', 'header' => TRUE],
          $employee->department,
        ],
        [
          ['data' => 'Country', 'header' => TRUE],
          $employee->country,
        ],
        [
          ['data' => 'State', 'header' => TRUE],
          $employee->state,
        ],
        [
          ['data' => 'Address', 'header' => TRUE],
          $employee->address,
        ],
    ];
    $content['details'] = [
      '#type' => 'table',
      '#rows' => $rows,
      '#attributes' => ['class' => ['employee-detail']]
    ];
    $content['edit'] = [
      '#type' => 'link',
      '#title' => 'Edit',
      '#attributes' => ['class' => ['button button--primary']],
      '#url' => Url::fromRoute('employee.edit',['employee' => $employee->id])
    ];
    $content['delete'] = [
      '#type' => 'link',
      '#title' => 'Delete',
      '#attributes' => ['class' => ['button']],
      '#url' => Url::fromRoute('employee.delete',['id' => $employee->id]),
    ];
    if ($js == 'ajax') {
      $modal_title = t('Employee #@id',['@id' => $employee->id]);
      $options = [
        'dialogClass' => 'popup-dialog-class',
        'width' => '70%',
        'height' => '80%'
      ];
      $response = new AjaxResponse();
      $response->addCommand(new OpenModalDialogCommand(
        $modal_title, $content, $options));
      return $response;
    } else {
      return $content;
    }
  }
  /**
   * Callback for opening the employee quick edit form in modal.
   */
  public function openQuickEditModalForm($employee = NULL) {
    if($employee == 'invalid'){
      drupal_set_message(t('Invalid employee record'), 'error');
      return new RedirectResponse(Drupal::url('employee.list'));
    }
    $response = new AjaxResponse();
    // Get the form using the form builder global
    //$modal_form = \Drupal::formBuilder()
    //->getForm('Drupal\employee\form\EmployeeQuickEditForm', $employee);
    $modal_form = $this->form_builder
      ->getForm('Drupal\employee\forms\EmployeeQuickEditForm', $employee);
    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(
      new OpenModalDialogCommand(t('Quick Edit Employee #@id',
      ['@id' => $employee->id]), $modal_form, ['width' => '800']
    ));
    return $response;
  }
  /**
   * Callback for opening the employee mail form in modal.
   */
  public function openEmailModalForm($employee = NULL) {
    if($employee == 'invalid'){
      drupal_set_message(t('Invalid employee record'), 'error');
      return new RedirectResponse(Drupal::url('employee.list'));
    }
    $response = new AjaxResponse();
    // Get the form using the form builder global
    $modal_form = $this->form_builder
      ->getForm('Drupal\employee\forms\EmployeeMailForm', $employee);
    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(
      new OpenModalDialogCommand(
        t('Send mail to: @email',['@email' => $employee->email]),
        $modal_form, ['width' => '800']
    ));
    return $response;
  }
}
