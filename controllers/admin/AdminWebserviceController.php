<?php
/*
* 2007-2011 PrestaShop 
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2011 PrestaShop SA
*  @version  Release: $Revision: 7499 $
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class AdminWebserviceControllerCore extends AdminController
{
	// this will be filled later
	public $fields_form = array('webservice form');

	public function __construct()
	{
	 	$this->table = 'webservice_account';
	 	$this->className = 'WebserviceKey';
	 	$this->lang = false;
	 	$this->edit = true;
	 	$this->delete = true;
 		$this->id_lang_default = Configuration::get('PS_LANG_DEFAULT');
		
		$this->fieldsDisplay = array(
			'key' => array('title' => $this->l('Key'), 'align' => 'center', 'width' => 32),
			'active' => array('title' => $this->l('Enabled'), 'align' => 'center', 'active' => 'status', 'type' => 'bool', 'orderby' => false),
			'description' => array('title' => $this->l('Key description'), 'align' => 'center', 'orderby' => false)
		);
		
		if (file_exists(_PS_ROOT_DIR_.'/.htaccess'))
			$this->options = array(
				'general' => array(
					'title' =>	$this->l('Configuration'),
					'fields' =>	array(
						'PS_WEBSERVICE' => array('title' => $this->l('Enable PrestaShop Webservice:'), 
							'desc' => $this->l('Before activating the webservice, you must be sure to: ').
												'<ol><li>'.$this->l('be certain URL rewrite is available on this server').
												'</li><li>'.$this->l('be certain that the 5 methods GET, POST, PUT, DELETE and HEAD are supported by this server').
												'</li></ol>', 
							'cast' => 'intval',
							'type' => 'bool'),
					),
				),
			);

		parent::__construct();
	}
	
	protected function afterAdd($object)
	{
		WebserviceKey::setPermissionForAccount($object->id, Tools::getValue('resources', array()));
	}
	
	protected function afterUpdate($object)
	{
		WebserviceKey::setPermissionForAccount($object->id, Tools::getValue('resources', array()));
	}
	
	public function checkForWarning()
	{
		if (!file_exists(_PS_ROOT_DIR_.'/.htaccess'))
			$this->warnings[] = $this->l('In order to enable the PrestaShop Webservice, please generate the .htaccess file via the "Generators" tab (in the "Tools" tab).');
		if (strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') === false)
			$this->warnings[] = $this->l('To avoid operating problems, please use an Apache server.');
		{
			if (function_exists('apache_get_modules'))
			{
				$apache_modules = apache_get_modules();
				if (!in_array('mod_auth_basic', $apache_modules))
					$this->warnings[] = $this->l('Please activate the Apache module \'mod_auth_basic\' to allow authentication of PrestaShop webservice.');
				if (!in_array('mod_rewrite', $apache_modules))
					$this->warnings[] = $this->l('Please activate the Apache module \'mod_rewrite\' to allow using the PrestaShop webservice.');
			}
			else
			{
				$this->warnings[] = $this->l('We could not check if basic authentication and rewrite extensions are activated. Please manually check if they are activated in order to use the PrestaShop webservice.');
			}
		}
		if (!extension_loaded('SimpleXML'))
			$this->warnings[] = $this->l('Please activate the PHP extension \'SimpleXML\' to allow testing of PrestaShop webservice.');
		if (!configuration::get('PS_SSL_ENABLED'))
			$this->warnings[] = $this->l('If possible, it is preferable to use SSL (https) for webservice calls, as it avoids the security issues of type "man in the middle".');
		

		foreach ($this->_list as $k => $item)
			if ($item['is_module'] && $item['class_name'] && $item['module_name'] && 
				($instance = Module::getInstanceByName($item['module_name'])) && 
				!$instance->useNormalPermissionBehaviour())
				unset($this->_list[$k]);
		$this->initList();
	}
	
	/** @todo : to fill $this->fields_form in order to generate
	 * the form automatically.. 
	 *
	 */
	public function initForm($isMainTab = true)
	{
		$content = '';
		if (!($obj = $this->loadObject(true)))
			return;
			
		$content = '
		<form action="'.self::$currentIndex.'&submitAdd'.$this->table.'=1&token='.$this->token.'" method="post" enctype="multipart/form-data">
		'.($obj->id ? '<input type="hidden" name="id_'.$this->table.'" value="'.$obj->id.'" />' : '').'
			<fieldset><legend><img src="../img/admin/access.png" />'.$this->l('Webservice Accounts').'</legend>
				<label>'.$this->l('Key:').'</label>
				<div class="margin-form">
					<input type="text" size="32" name="key" id="code" value="'.htmlentities(Tools::getValue('key', $obj->key), ENT_COMPAT, 'UTF-8').'" />
					<input type="button" value="'.$this->l('   Generate!   ').'" class="button" onclick="gencode(32)" />
					<sup>*</sup>
					<p class="clear">'.$this->l('Webservice account key').'</p>
				</div>
				<label>'.$this->l('Key description').'</label>
				<div class="margin-form">
					<textarea rows="3" style="width:400px" name="description">'.htmlentities(Tools::getValue('description', $obj->description), ENT_COMPAT, 'UTF-8').'</textarea>
					<p class="clear">'.$this->l('Key description').'</p>
				</div>
				<label>'.$this->l('Status:').' </label>
				<div class="margin-form">
					<input type="radio" name="active" id="active_on" value="1" '.((!$obj->id OR Tools::getValue('active', $obj->active)) ? 'checked="checked" ' : '').'/>
					<label class="t" for="active_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Enabled').'" /></label>
					<input type="radio" name="active" id="active_off" value="0" '.((!Tools::getValue('active', $obj->active) AND $obj->id) ? 'checked="checked" ' : '').'/>
					<label class="t" for="active_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('Disabled').'" /></label>
				</div>
				<label>'.$this->l('Permissions:').' </label>
				<div class="margin-form">
					<p>'.$this->l('Set the resource permissions for this key:').'</p>
					<table border="0" cellspacing="0" cellpadding="0" class="permissions">
						<thead>
							<tr>
								<th>'.$this->l('Resource').'</th>
								<th width="30"></th>
								<th width="50">'.$this->l('View (GET)').'</th>
								<th width="50">'.$this->l('Modify (PUT)').'</th>
								<th width="50">'.$this->l('Add (POST)').'</th>
								<th width="50">'.$this->l('Delete (DELETE)').'</th>
								<th width="50">'.$this->l('Fast view (HEAD)').'</th>
							</tr>
							
						</thead>
						<tbody>
						<tr class="all" style="vertical-align:cen">
								<th></th>
								<th></th>
								<th><input type="checkbox" class="all_get get " /></th>
								<th><input type="checkbox" class="all_put put " /></th>
								<th><input type="checkbox" class="all_post post " /></th>
								<th><input type="checkbox" class="all_delete delete" /></th>
								<th><input type="checkbox" class="all_head head" /></th>
							</tr>
		';
		$ressources = WebserviceRequest::getResources();
		$permissions = WebserviceKey::getPermissionForAccount($obj->key);
		foreach ($ressources as $resourceName => $resource)
			$content .= '
							<tr>
								<th>'.$resourceName.'</th>
								<th><input type="checkbox" class="all"/></th>
								<td><input type="checkbox" '.(isset($ressources[$resourceName]['forbidden_method']) && in_array('GET', $ressources[$resourceName]['forbidden_method']) ? 'disabled="disabled"' : '').' class="get" name="resources['.$resourceName.'][GET]" '.(isset($permissions[$resourceName]) && in_array('GET', $permissions[$resourceName]) ? 'checked="checked"' : '').' /></td>
								<td><input type="checkbox" '.(isset($ressources[$resourceName]['forbidden_method']) && in_array('PUT', $ressources[$resourceName]['forbidden_method']) ? 'disabled="disabled"' : '').' class="put" name="resources['.$resourceName.'][PUT]" '.(isset($permissions[$resourceName]) && in_array('PUT', $permissions[$resourceName]) ? 'checked="checked"' : '').'/></td>
								<td><input type="checkbox" '.(isset($ressources[$resourceName]['forbidden_method']) && in_array('POST', $ressources[$resourceName]['forbidden_method']) ? 'disabled="disabled"' : '').' class="post" name="resources['.$resourceName.'][POST]" '.(isset($permissions[$resourceName]) && in_array('POST', $permissions[$resourceName]) ? 'checked="checked"' : '').'/></td>
								<td><input type="checkbox" '.(isset($ressources[$resourceName]['forbidden_method']) && in_array('DELETE', $ressources[$resourceName]['forbidden_method']) ? 'disabled="disabled"' : '').' class="delete" name="resources['.$resourceName.'][DELETE]" '.(isset($permissions[$resourceName]) && in_array('DELETE', $permissions[$resourceName]) ? 'checked="checked"' : '').'/></td>
								<td><input type="checkbox" '.(isset($ressources[$resourceName]['forbidden_method']) && in_array('HEAD', $ressources[$resourceName]['forbidden_method']) ? 'disabled="disabled"' : '').' class="head" name="resources['.$resourceName.'][HEAD]" '.(isset($permissions[$resourceName]) && in_array('HEAD', $permissions[$resourceName]) ? 'checked="checked"' : '').'/></td>
							</tr>';
		$content .= '
						</tbody>
					</table>
				</div>
				<div class="margin-form">
					<input type="submit" value="'.$this->l('   Save   ').'" name="submitAdd'.$this->table.'" class="button" />
				</div>
				<div class="small"><sup>*</sup> '.$this->l('Required field').'</div>
			</fieldset>
		</form>';
		$this->tpl_form_vars['custom_form'] = $content;
		return parent::initForm();
	}

	public function postProcess()
	{
		if (Tools::getValue('key') && strlen(Tools::getValue('key')) < 32)
			$this->_errors[] = Tools::displayError($this->l('Key length must be 32 character long'));
		if (WebserviceKey::keyExists(Tools::getValue('key')) && !Tools::getValue('id_webservice_account'))
			$this->_errors[] = Tools::displayError($this->l('Key already exists'));
		return parent::postProcess();
	}

	public function initContent()
	{
		$content = '';
		// Include other tab in current tab
		if ($this->includeSubTab('display', array('submitAdd2', 'add', 'update', 'view'))){}

		// Include current tab
		elseif ((Tools::getValue('submitAdd'.$this->table) AND sizeof($this->_errors)) OR isset($_GET['add'.$this->table]))
		{
			if ($this->tabAccess['add'] === '1')
			{
				$this->display = 'add';
//				$content .= $this->initForm();
				if ($this->tabAccess['view'])
					$content .= '<br /><br /><a href="'.((Tools::getValue('back')) ? Tools::getValue('back') : self::$currentIndex.'&token='.$this->token).'"><img src="../img/admin/arrow2.gif" /> '.((Tools::getValue('back')) ? $this->l('Back') : $this->l('Back to list')).'</a><br />';
			}
			else
				$content .= $this->l('You do not have permission to add here');
		}
		elseif (isset($_GET['update'.$this->table]))
		{
			if ($this->tabAccess['edit'] === '1' OR ($this->table == 'employee' AND $this->context->employee->id == Tools::getValue('id_employee')))
			{
				$content .= $this->initForm();
				if ($this->tabAccess['view'])
					$content .= '<br /><br /><a href="'.((Tools::getValue('back')) ? Tools::getValue('back') : self::$currentIndex.'&token='.$this->token).'"><img src="../img/admin/arrow2.gif" /> '.((Tools::getValue('back')) ? $this->l('Back') : $this->l('Back to list')).'</a><br />';
			}
			else
				$content .= $this->l('You do not have permission to edit here');
		}
		elseif (isset($_GET['view'.$this->table]))
			$this->{'view'.$this->table}();
		else
		{
			$this->checkForWarning();
			
/*
			$this->getList($this->context->language->id);
			$this->displayList();
			
			$this->displayRequiredFields();
			$this->includeSubTab('display');
			$assos_shop = Shop::getAssoTables();
			if (isset($assos_shop[$this->table]) AND $assos_shop[$this->table]['type'] == 'shop')
				$this->displayAssoShop();
			elseif (isset($assos_shop[$this->table]) AND $assos_shop[$this->table]['type'] == 'group_shop')
				$this->displayAssoShop('group_shop');
			$this->displayOptionsList();
*/
		}
		parent::initContent();
	}

}
