<?php 

require_once '../vendor/autoload.php';

namespace NishealJ\CDLI_Latex2HTML;

class CDLI_Latex2HTML
{
    public function convert($file = "")
    {
        
      $data =  nl2br( file_get_contents($file) );

      preg_match_all('/.begin{document}(.*).end{document}/s', $data, $output_array);

      $data = $output_array[1][0];

      $data = preg_replace('/%(.*)/', '', $data); 
      $data = preg_replace('/[^a-zA-Z0-9]bf [^a-zA-Z0-9]S/', '', $data); 
      $data = preg_replace('/.nocite{(.*?)}/', '', $data); 
      $data = preg_replace('/.thispagestyle{(.*?)}/', '', $data); 
      $data = preg_replace('/.newpage/', '', $data);
      $data = preg_replace('/.begin{flushleft}(.*).end{flushleft}/s','', $data);
      $data = preg_replace('/.begingroup(.*).endgroup/s','', $data);
      $data = preg_replace('/.printbibliography(.*)/', '', $data);
      $data = str_replace('\\&',"and", $data);
      preg_match_all('/[^b]section.{}[<br \/>]*\n*\t*{[^}]*}/ms', $data, $all_sections);
      preg_match_all('/.subsection.{}[<br \/>]*\n*\t*{[^}]*}/ms', $data, $all_sub_sections);

      preg_match_all('/.superscript{[^}]*}[}]*/ms', $data, $all_superscript);
      preg_match_all('/.subscript{[^}]*}[}]*/ms', $data, $all_subscript);


      // For sections.
      foreach($all_sections[0] as $section) {
        $HTMLSection = str_replace("\n","", $section);
        $HTMLSection = str_replace("\t","", $HTMLSection);
        $HTMLSection = str_replace("\section","", $HTMLSection);
        $HTMLSection = str_replace("*","", $HTMLSection);
        $HTMLSection = str_replace("{","", $HTMLSection);
        $HTMLSection = str_replace("}","", $HTMLSection);
        $HTMLSection = str_replace("","", $HTMLSection);

        $data = str_replace($section,"<h1>".$HTMLSection."</h1>", $data);
      }

      // For sub sections.
      foreach($all_sub_sections[0] as $sbsection) {
        $HTMLsbSection = str_replace("\n","", $sbsection);
        $HTMLsbSection = str_replace("\t","", $HTMLsbSection);
        $HTMLsbSection = str_replace("\subsection","", $HTMLsbSection);
        $HTMLsbSection = str_replace("*","", $HTMLsbSection);
        $HTMLsbSection = str_replace("{","", $HTMLsbSection);
        $HTMLsbSection = str_replace("}","", $HTMLsbSection);
        $HTMLsbSection = str_replace("","", $HTMLsbSection);

        $data = str_replace($sbsection,"<span class=second-heading>".$HTMLsbSection."</span>", $data);
      }

      // For longtables.
      preg_match_all('/.begin{longtable}(.*?).end{longtable}/ms', $data, $all_tables);
      foreach($all_tables[0] as $table ) {
        $table = str_replace('\\&',"and", $table);
        $AllTables = explode('\\\\',$table);
        
        $PopCount = array();

        foreach($AllTables as $AllTablesI) {
          preg_match_all('/&/', $AllTablesI, $output_array);
          $mai = count($output_array[0]);
          array_push($PopCount,$mai);
        }
        $values = array_count_values($PopCount);
        arsort($values);
        $popular = array_slice(array_keys($values), 0, 5, true);

        $HTMLTable ='';
        foreach($AllTables as $AllTablesI) {
          $HTMLTable =   $HTMLTable .'<tr>';
          $AllTablesPop = explode('&',$AllTablesI);
          if(($popular[0]+1)!=count($AllTablesPop)){
            // Print log details here.
          }
          for($i=0;$i<count($AllTablesPop);$i++){
            if($AllTablesPop[$i]!=='' && $AllTablesPop[$i]!==' ') {
              $HTMLTable = $HTMLTable.'<td>'.$AllTablesPop[$i].'</td>';
            }
          }
          $HTMLTable =   $HTMLTable .'</tr>';
        }

        $data = str_replace($table,"<table>".$HTMLTable."</table>", $data);

      }


      foreach($all_superscript[0] as $superscript) {
        $PopCount = 0;
        $HTMLSuperScript="";
        $HTMLSuperScriptInc = str_replace("\superscript{","", $superscript);
        for($i=0;$i<(strlen($HTMLSuperScriptInc)-1);$i++){
          if($HTMLSuperScriptInc[$i]=='{'){
            $PopCount++;
          }
          if($HTMLSuperScriptInc[$i]=='}'){
            $PopCount--;
          }
          if( $PopCount>=0){
            $HTMLSuperScript = $HTMLSuperScript.$HTMLSuperScriptInc[$i];
          }
        }  
        $HTMLSuperScriptInc_ex = "\superscript{".$HTMLSuperScript."}";
        $data = str_replace($HTMLSuperScriptInc_ex,"<sup>".$HTMLSuperScript."</sup>", $data);
      }

      foreach($all_subscript[0] as $subscript) {
        $PopCount = 0;
        $HTMLSubScript="";
        $HTMLSubScriptInc = str_replace("\subscript{","", $subscript);
        for($i=0;$i<(strlen($HTMLSubScriptInc)-1);$i++){
          if($HTMLSubScriptInc[$i]=='{'){
            $PopCount++;
          }
          if($HTMLSubScriptInc[$i]=='}'){
            $PopCount--;
          }
          if( $PopCount>=0){
            $HTMLSubScript = $HTMLSubScript.$HTMLSubScriptInc[$i];
          }
        }  
        $HTMLSubScriptInc_ex = "\subscript{".$HTMLSubScript."}";
        $data = str_replace($HTMLSubScriptInc_ex,"<sub>".$HTMLSubScript."</sub>", $data);
      }

      // Extract footnotes.
      $footnotes_all="";
      $zx=1;
      for($i=0;$i<(strlen($data)-1);$i++) {
        if($data[$i]=='\\' ) {
          $SearchKeyFooter = substr($data,$i,10);
          if($SearchKeyFooter=='\\footnote{') {
            $PopCount = 1 ;
            $ix = 10;
            while($PopCount>0) {
                  if($data[$i+$ix]=='{'){
                    $PopCount++;
                  }
                  if($data[$i+$ix]=='}'){
                    $PopCount--;
                  }
                  $SearchKeyFooter = $SearchKeyFooter.$data[$i+$ix];
                  $ix++;
            }
            $i = $i + $ix;
            
            $data = str_replace($SearchKeyFooter,"<a href=\#f".$zx.">[".$zx."]</a>", $data);
            $SearchKeyFooter = substr($SearchKeyFooter, 0, -1);
            $SearchKeyFooter = str_replace('\\footnote{',"", $SearchKeyFooter);
            $footnotes_all=$footnotes_all."<li id='f".$zx."'>".$SearchKeyFooter."</li>";
            $zx++;
            // echo $footnotes_all;
          }
        }
      }

      $all_images =array();
      $ChecKinImages = 0;
      $zix=0;
      $ChecKinImagesStatic="";
      for($i=0;$i<(strlen($data)-1);$i++) {
        if($data[$i]=='\\' ) {
          $HTMLImg = substr($data,$i,14);;
          if($HTMLImg =='\\begin{figure}') {
            $ChecKinImages = 1;
            $zix = $i+14;
            while($ChecKinImages) {
          $HTMLImg2 = substr($data,$zix,12);
           $ChecKinImagesStatic = $ChecKinImagesStatic.$data[$zix];
              if($HTMLImg2 =='\\end{figure}') {
                $ChecKinImages=0;
                array_push($all_images,$ChecKinImagesStatic.addslashes("end{figure}"));
                $ChecKinImagesStatic = "";
              }
              $zix++;
            }
            $i = $zix;
          }
        }
      }

      $AllImagesHTML ="";
      foreach ( $all_images as $all_image) {
        $MakeImgHTML ="";
        preg_match('/.caption{(.*)}/', $all_image, $caption);
        preg_match('/includegraphics.*{(.*)}/', $all_image, $link);
        preg_match('/label{(.*)}/', $all_image, $label);
        $MakeImgHTML = "<img src='".$link[1]."'><span><b>".$label[1]."</b>: ".$caption[1]."</span>";
        $AllImagesHTML = $AllImagesHTML.$MakeImgHTML ;
        $data = str_replace($all_image,"", $data);
      }

      $data = str_replace("<br />","", $data);



      $parser = new PhpLatex_Parser();
      $parsedTree = $parser->parse($data);
      $htmlRenderer = new PhpLatex_Renderer_Html();
      $html = ' <link rel="stylesheet" href="some.css"><script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script><body>';
      $html = $html." ".$htmlRenderer->render($parsedTree);

      $html = str_replace("<h3>*</h3>","", $html);
      $html = $html."<br><br>".$AllImagesHTML."<br><ul>".$footnotes_all."</ul></body><script src='some.js'></script>";

      return html_entity_decode($html);
    }
}
