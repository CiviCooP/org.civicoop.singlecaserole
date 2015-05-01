<?php
/**
 * Class with util functions
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 30 April 2015
 * @license AGPL-3.0
 */

class CRM_Singlecaserole_Utils {

  /**
   * Method to get the JSON file with case_types and case_roles
   * @return mixed
   * @access public
   * @static
   */
  public static function getAllCaseTypesRoles() {
    $result = array();
    $paths = explode(PATH_SEPARATOR, get_include_path());
    foreach ($paths as $pathId => $pathName) {
      $filename = $pathName.'casetyperoles.json';
      if (file_exists($filename)) {
        $foundPath = $pathName;
      }
    }
    $jsonFile = file_get_contents($foundPath.'casetyperoles.json');
    $caseTypeRoles = json_decode($jsonFile, TRUE);
    foreach ($caseTypeRoles as $caseType => $caseRoles) {
      $caseRoleIds = array();
      $caseTypeId = self::getCaseTypeIdWithName($caseType);
      foreach ($caseRoles as $caseRole) {
        $caseRoleId = self::getCaseRoleIdWithName($caseRole);
        $caseRoleIds[] = $caseRoleId;
      }
      $result[$caseTypeId] = $caseRoleIds;
    }
    return $result;
  }

  /**
   * Method to select all roles for a case type
   *
   * @param int $caseTypeId
   * @return array
   * @access public
   * @static
   */
  public static function getCaseTypeRoles($caseTypeId) {
    $result = array();
    $caseTypesRoles = self::getAllCaseTypesRoles();
    if (isset($caseTypesRoles[$caseTypeId])) {
      $result = $caseTypesRoles[$caseTypeId];
    }
    return $result;
  }

  /**
   * Method to get relationship type id of case role with name_a_b
   *
   * @param string $caseRoleName
   * @return int|null $caseRoleId
   * @throws Exception when error from API
   * @access public
   * @static
   */
  public static function getCaseRoleIdWithName($caseRoleName) {
    $caseRoleId = null;
    if (!empty($caseRoleName)) {
      $relTypeParams = array(
        'name_a_b' => $caseRoleName,
        'is_active' => 1,
        'return' => 'id');
      try {
        $caseRoleId = civicrm_api3('RelationshipType', 'Getvalue', $relTypeParams);
      } catch (CiviCRM_API3_Exception $ex) {
        throw new Exception('Could not find a single relationship type with name_a_b '
          .$caseRoleName.' in extension Single Case Role, error from API RelationshipType Getvalue: '
          .$ex->getMessage());
      }
    }
    return $caseRoleId;
  }

  /**
   * Method to get relationship type name of case role with id
   *
   * @param int $caseRoleId
   * @return string|null $caseRoleName
   * @throws Exception when error from API
   * @access public
   * @static
   */
  public static function getCaseRoleNameWithId($caseRoleId) {
    $caseRoleName = null;
    if (!empty($caseRoleId)) {
      $relTypeParams = array(
        'id' => $caseRoleId,
        'is_active' => 1,
        'return' => 'label_a_b');
      try {
        $caseRoleName = civicrm_api3('RelationshipType', 'Getvalue', $relTypeParams);
      } catch (CiviCRM_API3_Exception $ex) {
        throw new Exception('Could not find a single relationship type with id '
          .$caseRoleId.' in extension Single Case Role, error from API RelationshipType Getvalue: '
          .$ex->getMessage());
      }
    }
    return $caseRoleName;
  }

  /**
   * Method to get case type id with case type name
   *
   * @param string $caseTypeName
   * @return array|null
   * @throws Exception when error from API
   * @access public
   * @static
   */
  public static function getCaseTypeIdWithName($caseTypeName) {
    $caseTypeId = null;
    if (!empty($caseTypeName)) {
      $optionGroupParams = array(
        'name' => 'case_type',
        'is_active' => 1,
        'return' => 'id');
      try {
        $optionGroupId = civicrm_api3('OptionGroup', 'Getvalue', $optionGroupParams);
        $optionValueParams = array(
          'option_group_id' => $optionGroupId,
          'name' => $caseTypeName,
          'return' => 'value'
        );
        try {
          $caseTypeId = civicrm_api3('OptionValue', 'Getvalue', $optionValueParams);
        } catch (CiviCRM_API3_Exception $ex) {
          throw new Exception('Could not find a case type with name ' . $caseTypeName
            . ', error from API OptionValue Getvalue: ' . $ex->getMessage());
        }
      } catch (CiviCRM_API3_Exception $ex) {
        throw new Exception('Could not find an active option group with the name case_type in
          extension Single Case Role, error from API OptionGroup Getvalue: ' . $ex->getMessage());
      }
    }
    return $caseTypeId;
  }
}