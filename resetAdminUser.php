<?php
require_once 'app/Mage.php';
Varien_Profiler::enable();
Mage::setIsDeveloperMode(true);
error_reporting(E_ALL);
ini_set('display_errors', 1);
umask(0);
Mage::app();

$deleteAllRolesRules = "SET GLOBAL FOREIGN_KEY_CHECKS=0;
						TRUNCATE `admin_rule`;
						TRUNCATE `admin_role`;
						TRUNCATE `admin_user`;
						SET GLOBAL FOREIGN_KEY_CHECKS=1;";
echo $deleteAllRolesRules;exit;

# Delete the current Admin User with Username 'admin'
$model = Mage::getModel('admin/user');
$username = 'admin';
try {
	$user = $model->loadByUsername($username);
	$user->delete();
	echo "Administrator with username $username deleted successfully !!!";
} catch(Exception $e) {
	exit($e->getMessage());
}

$adminRoleCreateSql = "INSERT INTO admin_role VALUES(1,0,1,1,'G',0,'Administrator',1,NULL,NULL);";
$adminRuleCeateSql = "INSERT INTO admin_rule values (8,1,'all',null,0,'G','allow');";
# Create a new administrator with username

/*INSERT INTO `admin_role` (parent_id,tree_level,sort_order,role_type,user_id,role_name) 
VALUES (1,2,0,'U',(SELECT user_id FROM admin_user WHERE username = 'myuser'),'Firstname');*/

try {
	$adminRole = Mage::getModel('admin/role')
					->setData(
						array(
							'role_id' => 1,
							'parent_id' => 0,
							'tree_level' => 1,
							'sort_order' => 1,
							'role_type' => 'G',
							'user_id' => $adminUser->getUserId(),
							'role_name' => 'Administrator',
							'gws_is_all' => 1,
							'gws_websites' => null,
							'gws_store_groups' => null
							)
						)->save();
	$adminRule = Mage::getModel('admin/rules')
					->setData(
						array(
							'rule_id' => 8,
							'role_id' => 1,
							'resource_id' => 'all',
							'privileges' => NULL,
							'assert_id' => 0,
							'role_type' => 'G',
							'permission' => 'allow'
							)
						)->save();
	$adminUser = Mage::getModel('admin/user')
					->setData(
						array(
							'username' => 'admin',
							'firstname' => 'Vivek',
							'lastname' => 'Shah',
							'email' => 'admin@localhost.com',
							'password' => 'admin123',
							'is_active' => 1
							)
						)->save();
	$adminUser->setRoleIds(array(1))
			->setRoleUserId($adminUser->getUserId())
			->saveRelations();
	echo "Administrator with username \"admin\" created successfully !!!";
} catch(Exception $e) {
	exit($e->getMessage());	
}