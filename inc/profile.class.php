<?php
class PluginTagProfile extends Profile {

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      return self::createTabEntry(__('Tag management', 'tag'));
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $tagprofile = new self();
      $tagprofile->showForm($item->getID());
      return true;
   }

   function showForm($ID, array $options = []) {
      if (!self::canView()) {
         return false;
      }

      echo "<div class='spaced'>";
      $profile = new Profile();
      $profile->getFromDB($ID);
      if ($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE])) {
         echo "<form method='post' action='".$profile->getFormURL()."'>";
      }

      $rights = [['itemtype'  => 'PluginTagTag',
                            'label'     => PluginTagTag::getTypeName(Session::getPluralNumber()),
                            'field'     => 'plugin_tag_tag']];
      $matrix_options['title'] = __('Tag management', 'tag');
      $profile->displayRightsChoiceMatrix($rights, $matrix_options);

      if ($canedit) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $ID]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }
}