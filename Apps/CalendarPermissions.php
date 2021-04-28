<?php

class CalendarPermissions{

    protected $db;
    
    public function __construct(){
        global $wpdb;
        $this->db = $wpdb;
    }
    
    /**
	* Get a list of permission groups for editing calendars
	*/
	public function getPermissionGroups(){
    	$groups = array();
    	
    	$res = $this->db->query("
    	   SELECT `itemID`, `nameFull` 
    	   FROM `sysUGroups` 
    	   WHERE `sysStatus` = 'active' AND `sysOpen` = '1' AND `nameSystem` NOT IN ('siteadmin', 'publicusers')
    	   ORDER BY `nameSystem`
    	   ");
    	if ($res->num_rows > 0){
        	while ($row = $this->db->fetch_assoc($res)){
            	$groups[$row["itemID"]] = trim($row["nameFull"]);
        	}
    	}
    	return $groups;
	}
	/**
	* Create a permissions record for a calendar
	* @access public
	* @param integer $calendarID
	* @param integer $groupID
	*/
	public function createCalendarPermission($calendarID, $groupID){
        
        $this->db->query(sprintf("INSERT IGNORE INTO `tblCalendarPermissions` (`calendarID`, `groupID`) values (%d, %d)",
            (int) $calendarID,
            (int) $groupID
        
        ));
    }
    /**
    * Delete all permission records for a specific calendar
    * @access public
    * @param integer $calendarID
    */
    public function removeCalendarPermissions($calendarID){
        
        $this->db->query(sprintf("DELETE FROM `tblCalendarPermissions`  WHERE `calendarID` = %d",
            (int) $calendarID        
        ));
    }
    /**
    * Get a list of permission calendars approved to a specific user
    * @access public
    * @return array
    */
    public function getCalendarPermissionGroups(){
    	$permissions = array();
    	
    	$res = $this->db->query(sprintf("
    	   SELECT c.`groupID`, c.`calendarID` 
    	   FROM `tblCalendarPermissions` AS c 
    	   INNER JOIN `sysUGLinks` AS g ON c.`groupID` = g.`groupID`
    	   WHERE g.`sysStatus` = 'active' AND g.`sysOpen` = '1' AND g.`userID` = %d",
    	       Quipp()->user()->getId()
    	   ));
    	if ($res->num_rows > 0){
        	while ($row = $this->db->fetch_assoc($res)){
            	$permissions[trim($row["calendarID"])] = $row["groupID"];
        	}
    	}
    	return $permissions;
    }
    /**
	* Get calendar permissions for admin calendar editing
	* @access public
	* @param integer $calendarID
	* @return array
	*/
	
	public function getCalendarPermissions($calendarID){
	    $permissions = array();
    	if ((int) $calendarID > 0){
        	$res = $this->db->query(sprintf("SELECT `groupID` FROM `tblCalendarPermissions` WHERE `calendarID` = %d",
        	   (int)$calendarID
        	));
        	if ($res->num_rows > 0){
            	while ($row = $this->db->fetch_assoc($res)){
                	$permissions[] = (int) $row['groupID'];
            	}
        	}
    	}
    	return $permissions;
	}
}