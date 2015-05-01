<?php

require_once 'CRM/Core/Page.php';

class CRM_Singlecaserole_Page_SingleCaseRole extends CRM_Core_Page {
  function run() {
    $relType         = CRM_Utils_Array::value('rel_type', $_REQUEST);
    $relContactID    = CRM_Utils_Array::value('rel_contact', $_REQUEST);
    $relationshipID  = CRM_Utils_Array::value('rel_id', $_REQUEST); // this used only to determine add or update mode
    $caseID          = CRM_Utils_Array::value('case_id', $_REQUEST);


    /*
     * extension Singlecaserole: validate if more than one instance of role is allowed
     */
    $status = $this->checkMultipleRolesAllowed($caseID, $relType, $relationshipID);
    if (empty($status)) {

      // check if there are multiple clients for this case, if so then we need create
      // relationship and also activities for each contacts

      // get case client list
      $clientList = CRM_Case_BAO_Case::getCaseClients($caseID);

      foreach ($clientList as $sourceContactID) {
        $relationParams = array(
          'relationship_type_id' => $relType . '_a_b',
          'contact_check' => array($relContactID => 1),
          'is_active' => 1,
          'case_id' => $caseID,
          'start_date' => date("Ymd"),
        );

        $relationIds = array('contact' => $sourceContactID);

        // check if we are editing/updating existing relationship
        if ($relationshipID && $relationshipID != 'null') {
          // here we need to retrieve appropriate relationshipID based on client id and relationship type id
          $caseRelationships = new CRM_Contact_DAO_Relationship();
          $caseRelationships->case_id = $caseID;
          $caseRelationships->relationship_type_id = $relType;
          $caseRelationships->contact_id_a = $sourceContactID;
          $caseRelationships->find();

          while ($caseRelationships->fetch()) {
            $relationIds['relationship'] = $caseRelationships->id;
            $relationIds['contactTarget'] = $relContactID;
          }
          $caseRelationships->free();
        }

        // create new or update existing relationship
        $return = CRM_Contact_BAO_Relationship::create($relationParams, $relationIds);

        $status = 'process-relationship-fail';
        if (CRM_Utils_Array::value(0, $return[4])) {
          $relationshipID = $return[4][0];
          $status = 'process-relationship-success';

          //create an activity for case role assignment.CRM-4480
          CRM_Case_BAO_Case::createCaseRoleActivity($caseID, $relationshipID, $relContactID);
        }
      }
    }

    $relation['status'] = $status;
    echo json_encode($relation);
    CRM_Utils_System::civiExit();
  }

  /**
   * Method to check if multiple case roles allowed, and if not see if there is already one on the case
   *
   * @param int $caseId
   * @param int $relTypeId
   * @param int $relationshipId (to check if we are adding or editing)
   * @return null|string
   */
  protected function checkMultipleRolesAllowed($caseId, $relTypeId, $relationshipId) {
    $status = null;
    $caseParams = array(
      'case_id' => $caseId,
      'return' => 'case_type_id');
    $caseTypeId = civicrm_api3('Case', 'Getvalue', $caseParams);
    $singleCaseRoles = CRM_Singlecaserole_Utils::getCaseTypeRoles($caseTypeId);
    if (!empty($singleCaseRoles)) {
      if (in_array($relTypeId, $singleCaseRoles)) {
        if ($this->roleAlreadyExistsOnCase($caseId, $relTypeId, $relationshipId) == TRUE) {
          $status = 'singlecaserole-error';
        }
      }
    }
  return $status;
  }

  /**
   * Method to determine if role is already on case (if $relationshipId is not null we are replacing else we are adding)
   *
   * @param int $caseId
   * @param int $relTypeId
   * @param int $relationshipId
   * @return bool
   * @access protected
   */
  protected function roleAlreadyExistsOnCase($caseId, $relTypeId, $relationshipId) {
    $alreadyExists = FALSE;
    $relationshipParams = array(
      'case_id' => $caseId,
      'relationship_type_id' => $relTypeId
    );
    try {
      $countRelations = civicrm_api3('Relationship', 'Getcount', $relationshipParams);
      if ($relationshipId == 'null') {
        if ($countRelations > 0) {
          $alreadyExists = TRUE;
        }
      } else {
        if ($countRelations > 1) {
          $alreadyExists = TRUE;
        }
      }
    } catch (CiviCRM_API3_Exception $ex) {}
    return $alreadyExists;
  }
}