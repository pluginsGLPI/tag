<?php
class PluginTagTag extends CommonDropdown {

   public static function getTypeName($nb=1) {
      return __('Tag', 'tag');
      //return _n('Tag', 'Tags', 'tag');
   }
   
   public static function getTagName($id_tag) {
      $obj = new self();
      $obj->getFromDB($id_tag);
      return $obj->fields['name'];
   }

   static function colorInput($name, $value) {
      echo "<div id='$name' style='width:105px'></div>";
   
      $JS = <<<JAVASCRIPT
      Ext.onReady(function() {
         //extjs color picker
         new Ext.Panel({
            renderTo:document.getElementById('$name'),
            plain:false,
            header:false,
            border:false,
            items:[{
                  xtype:'colorfield',
                  hideLabel:true,
                  value:'{$value}',
                  name:'$name',
                  colorSelector:'mixer'
            }]
         });
      });
JAVASCRIPT;
   
      echo "<script type='text/javascript'>";
      echo $JS;
      echo "</script>";
   }
   
   public function showForm($ID, $options = array()) {
      if (!$this->isNewID($ID)) {
         $this->check($ID, 'r');
      } else {
         $this->check(-1, 'w');
      }
      $options['colspan'] = 2;
      $options['target']  = Toolbox::getItemTypeFormURL(__CLASS__);
      $this->showTabs($options);
      $this->showFormHeader($options);
      echo '<table class="tab_cadre_fixe">';

      echo "<tr class='line0'><td>" . __('Name') . " <span class='red'>*</span></td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "</tr>";

      echo "<tr class='line1'><td>" . __('Description') . "</td>";
      echo "<td>";
      echo "<textarea name='comment' id ='comment' cols='45' rows='2' >" . $this->fields['comment'] . "</textarea>";
      //Html::initEditorSystem('comment');
      echo "</td>";
      echo "</tr>";

      echo "<tr class='line1'><td>" . __('HTML color', 'tag') . "</td>"; 
      echo "<td>";
      //echo "<textarea name='color' id ='color' cols='45' rows='2' >" . $this->fields['color'] . "</textarea>";
      self::colorInput("color", $this->fields["color"]);
      echo "</td>";
      echo "</tr>";
      
      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }

   public static function install(Migration $migration) {
      $table = getTableForItemType(__CLASS__);
      if (!TableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                     `id` int(11) NOT NULL auto_increment,
                     `entities_id` int(11) NOT NULL DEFAULT '0',
                     `is_recursive` tinyint(1) NOT NULL DEFAULT '1',
                     `name` varchar(255) NOT NULL DEFAULT '',
                     `comment` text collate utf8_unicode_ci,
                     `color` varchar(50) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
                     PRIMARY KEY (`id`),
                     KEY `name` (`name`)
                     ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $GLOBALS['DB']->query($query) or die($GLOBALS['DB']->error());
      }
      
      return true;
      }

   public static function uninstall() {
      $query = "DROP TABLE IF EXISTS `" . getTableForItemType(__CLASS__) . "`";
      return $GLOBALS['DB']->query($query) or die($GLOBALS['DB']->error());
   }
   
   /**
    * Définition du nom de l'onglet
    **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
   
      return _n('Associated item', 'Associated items', 2);
   }
   
   /**
    * Définition du contenu de l'onglet
    **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
   
      $monplugin = new PluginTagTagItem();
      $ID = $item->getField('id');
      $monplugin->showForTag($item);
      return true;
   }
   
   function defineTabs($options=array()){
      
      $ong = array();
      $this->addStandardTab('PluginTagTag', $ong, $options);
   
      return $ong;
   }
   
   public function cleanDBonPurge() {
      global $DB;
      
      $query = "DELETE FROM `glpi_plugin_tag_tagitems`
                WHERE `plugin_tag_tags_id`=".$this->fields['id'];
      $DB->query($query);
   }
   
   /**
    * @see CommonDBTM::getSpecificMassiveActions()
    **/
   function getSpecificMassiveActions($checkitem=NULL) {
      return CommonDBTM::getSpecificMassiveActions($checkitem);
   }
   
   /**
    * Return the linked items (in computers_items)
    *
    * @return an array of linked items  like array('Computer' => array(1,2), 'Printer' => array(5,6))
    * @since version 0.84.4
    **/
   function getLinkedItems() {
      global $DB;
   
      $query = "SELECT `itemtype`, `items_id`
              FROM `glpi_computers_items`
              WHERE `computers_id` = '" . $this->fields['id']."'";
      $tab = array();
      foreach ($DB->request($query) as $data) {
         $tab[$data['itemtype']][$data['items_id']] = $data['items_id'];
      };
      return $tab;
   }
   
   /**
    * @see CommonDBTM::showSpecificMassiveActionsParameters()
    **/
   function showSpecificMassiveActionsParameters($input=array()) {
   
      switch ($input['action']) {
         default :
            return parent::showSpecificMassiveActionsParameters($input);
      }
      return false;
   }
   
   
   /**
    * @see CommonDBTM::doSpecificMassiveActions()
    **/
   function doSpecificMassiveActions($input=array()) {
   
      $res = array('ok'      => 0,
            'ko'      => 0,
            'noright' => 0);
      switch ($input['action']) {
         default :
            return parent::doSpecificMassiveActions($input);
      }
      return $res;
   }
   
   
   function getSearchOptions() {
      global $CFG_GLPI;
   
      $tab                       = array();
      $tab['common']             = __('Characteristics');

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Name');
      $tab[1]['massiveaction']   = true;
      $tab[1]['datatype']        = 'itemlink';
      
      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'comment';
      $tab[2]['name']            = __('Description');
      $tab[2]['massiveaction']   = true;
      $tab[2]['datatype']        = 'string';
      
      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'id';
      $tab[3]['name']            = __('ID');
      $tab[3]['massiveaction']   = false;
      $tab[3]['datatype']        = 'number';

      $tab[4]['table']           = 'glpi_entities';
      $tab[4]['field']           = 'completename';
      $tab[4]['linkfield']       = 'entities_id';
      $tab[4]['name']            = __('Entity');
      
      $tab[5]['table']           = $this->getTable();
      $tab[5]['field']           = 'is_recursive';
      $tab[5]['name']            = __('Child entities');
      $tab[5]['datatype']        = 'bool';
      
      return $tab;
   }
   
   static function cmp_Tag($a, $b) {
      return strcmp($a["name"], $b["name"]);
   }
   
   static function tagDropdownMultiple($options = array()) {
      global $CFG_GLPI;

      //default options
      $params['name']                = '_plugin_tag_tag_values';
      $params['rand']                = mt_rand();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      // multiple select : add [] to name
      $params['name'].= "[]";

      $itemtype = $_REQUEST['itemtype'];
      $obj = new $itemtype();

      // Object must be an instance of CommonDBTM (or inherint of this)
      if (!$obj instanceof CommonDBTM) {
        return;
      }

      $selected_id = array();
      $tag_item = new PluginTagTagItem();
      $found_items = $tag_item->find('items_id='.$_REQUEST['id'].' 
                                      AND itemtype="'.$_REQUEST['itemtype'].'"');

      foreach ($found_items as $found_item) {
         $selected_id[] = $found_item['plugin_tag_tags_id'];
      }

      $obj->getFromDB($_REQUEST['id']);
      $sel_attr = $obj->canUpdateItem() ? '' : ' disabled ';

      $tag = new self();
      $where = "";
      // restrict tag by entity if current object has entity
      if (isset($obj->fields['entities_id'])) {
         if ($itemtype == 'entity') {
            $where = getEntitiesRestrictRequest(" ", '', '', $obj->fields['id'], true);
         } else {
            $where = getEntitiesRestrictRequest(" ", '', '', $obj->fields['entities_id'], true);
         }
      }
      $found = $tag->find($where);

      echo "<span style='width:80%'>";
      echo "<select data-placeholder='".__('Choose tags...', 'tag')."' name='".$params['name']."'
                id='tag_select' multiple class='chosen-select-no-results' $sel_attr >";
      
      usort($found, array(__CLASS__, "cmp_Tag"));
      
      foreach ($found as $label) {
         $param = in_array($label['id'], $selected_id) ? ' selected ' : '';
         if (! empty($label['color'])) {
            $param .= 'data-color-option="'.$label['color'].'"';
         }
         echo '<option value="'.$label['id'].'" '.$param.'>'.$label['name'].'</option>';
      }
      echo "</select>";
      echo "</span>";

      echo "<script type='text/javascript' >\n
      window.updateTagSelectResults_".$params['rand']." = function () {
         
      }
      </script>";

      if (PluginTagTag::canCreate()) {
         // Show '+' button :
         echo "&nbsp;<img alt='' title=\"".__s('Add')."\" src='".$CFG_GLPI["root_doc"].
              "/pics/add_dropdown.png' style='cursor:pointer; margin-left:2px;'
              onClick=\"var w = window.open('".
               $CFG_GLPI['url_base']."/plugins/tag/front/tag.form.php?popup=1&amp;rand=".$params['rand']."', ".
               "'glpipopup', 'height=400, width=1000, top=100, left=100, scrollbars=yes' );w.focus();\">";
      }
   }
}
