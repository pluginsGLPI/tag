<?php
class PluginTagPdf extends PluginPdfCommon {
   
   function __construct(CommonGLPI $obj=NULL) {
      $this->obj = ($obj ? $obj : new Ticket()); //TODO
   }
   
   /**
    * Render tab managed by our plugin
    *
    * @param $pdf  : a simplePDF object
    * @param $item : the computer
    * @param $tab  : number
    *
    * @return name of function to be called to render the PDF
    */
   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {
   
      /*
      switch ($tab) {
         case '_main_' :
            $item->pdfMain($pdf);
            break;
   
         default :
            return false;
      }
      return true;*/
      
      //if ($item->getType() == COMPUTER_TYPE) {
      self::pdfForComputer($pdf, $item);
      return true;
      //}
   }
   
   /**
    * Render PDF of data provided by tag plugin to computer
    *
    * @param $pdf : a simplePDF object
    * @param $item : the computer
    *
    * @return nothing (buf PDF)
    **/
   static function pdfForComputer(PluginPdfSimplePDF $pdf, CommonGLPI $item) {
      $pdf->setColumnsSize(100);
      $pdf->displayTitle('<b>A great example</b>');
      $pdf->displaySpace();
   }
   
} 