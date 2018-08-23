<?php

/* * *****************************************************************************
 * PHP Invoice                                                                  *
 *                                                                              *
 * Version: 1.0	                                                               *
 * Author:  Farjad Tahir	                                    				   *
 * http://www.splashpk.com                                                      *
 * ***************************************************************************** */
require_once('fpdf/__autoload.php');

class Pdf extends FPDF_rotation {

    private $font = 'helvetica';   /* Font Name : See inc/fpdf/font for all supported fonts */
    private $columnOpacity = 0.06;    /* Items table background color opacity. Range (0.00 - 1) */
    private $columnSpacing = 0.3;     /* Spacing between Item Tables */
    private $referenceformat = array('.', ','); /* Currency formater */
    private $margins = array('l' => 15,
        't' => 15,
        'r' => 15); /* l: Left Side , t: Top Side , r: Right Side */
    public $lang;
    public $document;
    public $type;
    public $reference;
    public $logo;
    public $color;
    public $date;
    public $time;
    public $due;
    public $client_type;
    public $doc_type;
    public $from;
    public $to;
    public $items;
    public $totals;
    public $badge;
    public $addText;
    public $dataFirma;
    public $footernote;
    public $dimensions;
    public $display_tofrom = true;
    


    /*     * ****************************************
     * Class Constructor               		 *
     * param : Page Size , Currency, Language *
     * **************************************** */

    public function __construct($size = 'A4', $currency = '€', $language = 'it') {
        $this->columns = 7;
        $this->items = array();
        $this->totals = array();
        $this->addText = array();
        $this->firstColumnWidth = 20;
        $this->currency = $currency;
        $this->maxImageDimensions = array(230, 130);
        $this->setLanguage($language);
        $this->setDocumentSize($size);
        $this->setColor("#222222");
        $this->FPDFMETHOD('P', 'mm', array($this->document['w'], $this->document['h']));
        $this->AliasNbPages();
        $this->SetMargins($this->margins['l'], $this->margins['t'], $this->margins['r']);
        
    }

    private function setLanguage($language) {
        $this->language = $language;
        include('fpdf/languages/' . $language . '.inc');
        $this->lang = $lang;
    }

    private function setDocumentSize($dsize) {
        switch ($dsize) {
            case 'A4':
                $document['w'] = 210;
                $document['h'] = 297;
                break;
            case 'letter':
                $document['w'] = 215.9;
                $document['h'] = 279.4;
                break;
            case 'legal':
                $document['w'] = 215.9;
                $document['h'] = 355.6;
                break;
            default:
                $document['w'] = 210;
                $document['h'] = 297;
                break;
        }
        $this->document = $document;
    }

    private function resizeToFit($image) {
        list($width, $height) = getimagesize($image);
        $newWidth = $this->maxImageDimensions[0] / $width;
        $newHeight = $this->maxImageDimensions[1] / $height;
        $scale = min($newWidth, $newHeight);
        return array(
            round($this->pixelsToMM($scale * $width)),
            round($this->pixelsToMM($scale * $height))
        );
    }

    private function pixelsToMM($val) {
        $mm_inch = 25.4;
        $dpi = 96;
        return ($val * $mm_inch) / $dpi;
    }

    private function hex2rgb($hex) {
        $hex = str_replace("#", "", $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        $rgb = array($r, $g, $b);
        return $rgb;
    }

    private function br2nl($string) {
        return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
    }

    public function isValidTimezoneId($zone) {
        try {
            new DateTimeZone($zone);
        } catch (Exception $e) {
            return FALSE;
        }
        return TRUE;
    }

    public function setTimeZone($zone = "") {
        if (!empty($zone) and $this->isValidTimezoneId($zone) === TRUE) {
            date_default_timezone_set($zone);
        }
    }

    public function setType($title) {
        $this->title = $title;
    }

    public function setColor($rgbcolor) {
        $this->color = $this->hex2rgb($rgbcolor);
    }

    public function setDate($date) {
        $this->date = $date;
    }

    public function setTime($time) {
        $this->time = $time;
    }

    public function setDue($date) {
        $this->due = $date;
    }

    public function setClient($data) {
        $this->client_type = $data;
    }

    public function setDoctype($data) {
        $this->doc_type = $data;  
    }

    public function setLogo($logo = 0, $maxWidth = 0, $maxHeight = 0) {
        if ($maxWidth and $maxHeight) {
            $this->maxImageDimensions = array($maxWidth, $maxHeight);
        }
        $this->logo = $logo;
        $this->dimensions = $this->resizeToFit($logo);
    }

    public function hide_tofrom() {
        $this->display_tofrom = false;
    }

    public function setFrom($data) {
        $this->from = $data;
    }

    public function setTo($data) {
        $this->to = $data;
    }

    public function setAzienda($reference) {
        $this->reference = $reference;
    }

    public function setNumberFormat($decimals, $thousands_sep) {
        $this->referenceformat = array($decimals, $thousands_sep);
    }

    public function flipflop() {
        $this->flipflop = true;
    }

    public function addItem($isbn, $editor, $author, $description, $price, $quantity, $total, $num = 0) {
        
        $p['isbn'] = preg_replace("/[^0-9]/", "", $isbn); //isbn
        
        $p['editor'] = $editor; //editore

        $p['author'] = $author; //autore

        $p['description'] = $description; //descrizione
        
        $p['price'] = $price; //prezzo
        
        $p['qty'] = $quantity; //quantita'
        
        $p['total'] = $total; //importo
        
        //$p['num'] = $num;
        
        /*if ($vat !== false) {
            $p['vat'] = $vat;
            if (is_numeric($vat)) {
                $p['vat'] = $this->currency . ' ' . number_format($vat, 2, $this->referenceformat[0], $this->referenceformat[1]);
            }
            $this->vatField = true;
            $this->columns = 5;
        }*/
        
        /*if ($discount !== false) {
            $this->firstColumnWidth = 58;
            $p['discount'] = $discount;
            if (is_numeric($discount)) {
                $p['discount'] = $this->currency . ' ' . number_format($discount, 2, $this->referenceformat[0], $this->referenceformat[1]);
            }
            $this->discountField = true;
            $this->columns = 7;
        }*/
        $this->items[] = $p;
    }

    public function addTotal($name, $value, $colored = FALSE) {
        $t['name'] = $name;
        $t['value'] = $value;
        if (is_numeric($value)) {
            $t['value'] = $this->currency . ' ' . number_format($value, 2, $this->referenceformat[0], $this->referenceformat[1]);
        }
        $t['colored'] = $colored;
        $this->totals[] = $t;
    }

     public function addDataFirma($data) {
        $this->dataFirma = $data;
     }
    
    public function addTitle($title) {
        $this->addText[] = array('title', $title);
    }

    public function addParagraph($paragraph) {
        $paragraph = $this->br2nl($paragraph);
        $this->addText[] = array('paragraph', $paragraph);
    }
    
    
    
    

    public function addBadge($badge) {
        $this->badge = $badge;
    }

    public function setFooternote($note) {
        $this->footernote = $note;
    }

    public function render($name = '', $destination = '') {
        $this->AddPage();
        $this->Body();
        $this->AliasNbPages();
        $this->Output($name, $destination);
    }

    public function Header() {

        if (isset($this->logo) and ! empty($this->logo)) {
            $this->Image($this->logo, $this->margins['l'], $this->margins['t'], $this->dimensions[0], $this->dimensions[1]);
        }

//	        //Title
        $this->SetTextColor(0, 0, 0);
//		$this->SetFont($this->font,'B',20);
	    //$this->Cell(0,0,/*iconv("UTF-8", "ISO-8859-1",*/strtoupper($this->title)/*)*/,0,1,'R');
        $this->SetFont($this->font, '', 9);
        $this->Ln(3);
//		
        $lineheight = 5;
        //Calculate position of strings
        $this->SetFont($this->font, 'B', 9);
        $positionX = $this->document['w'] - $this->margins['l'] - $this->margins['r'] - max(strtoupper($this->GetStringWidth($this->lang['number'])), strtoupper($this->GetStringWidth($this->lang['date'])), strtoupper($this->GetStringWidth($this->lang['due']))) - 35;
        
        
         $this->SetFont($this->font, '', 10);
        //Number
        if (!empty($this->reference)) {
            $this->Multicell(0, 4, iconv('utf-8', 'cp1252', $this->reference), 0, 'R', false);
        }


        //First page
        if ($this->PageNo() == 1) {
            if (($this->margins['t'] + $this->dimensions[1]) > $this->GetY()) {
                $this->SetY($this->margins['t'] + $this->dimensions[1] + 5);
            } else {
                $this->SetY($this->GetY() + 10);
            }
            $this->Ln(5);
//			$this->SetFillColor($this->color[0],$this->color[1],$this->color[2]);
//			$this->SetTextColor($this->color[0],$this->color[1],$this->color[2]);
//			
//			$this->SetDrawColor($this->color[0],$this->color[1],$this->color[2]);
            $this->SetFont($this->font, 'B', 9);
            $width = ($this->document['w'] - $this->margins['l'] - $this->margins['r']) / 2;
           /* if (isset($this->flipflop)) {

                $to = $this->lang['to'];
                $from = $this->lang['from'];

                $this->lang['to'] = $from;
                $this->lang['from'] = $to;

                $to = $this->to;
                $from = $this->from;

                $this->to = $from;
                $this->from = $to;

            }*/

            //cliente
            $this->Cell($width, $lineheight, strtoupper($this->client_type), 0, 0, 'L');
            
            //tipo di documento
            $this->Cell(0,$lineheight,strtoupper($this->doc_type),0,0,'L');
            



            $this->Ln(5);


           $this->Multicell(0,6, iconv('utf-8', 'cp1252',$this->to),1,'L',false);

            //                        
            //                        $this->Ln(15);       
            //                        $this->Rect(20,50,180,20);      
            //                        $this->SetFont('Arial','',10);
            //                        $cell = 'This is my disclaimer.';
            //                        $this->Cell($this->GetStringWidth($cell),3,$cell, 0, 'L');
            //                        $this->SetFont('Arial','B',10);
            //                        $boldCell = "THESE WORDS NEED TO BE BOLD.";
            //                        $this->Cell($this->GetStringWidth($boldCell),3,$boldCell, 0, 'L');
            //                        $this->SetFont('Arial','',10);
            //                        $cell = 'These words do not need to be bold.';
            //                        $this->Cell($this->GetStringWidth($cell),3,$cell, 0, 'L');
            //                   

            $this->Line($this->margins['l'], $this->GetY(), $this->margins['l'] + $width - 10, $this->GetY());
            $this->Line($this->margins['l'] + $width, $this->GetY(), $this->margins['l'] + $width + $width, $this->GetY());

        }
        //Table header
        if (!isset($this->productsEnded)) {
            $width_other = ($this->document['w'] - $this->margins['l'] - $this->margins['r'] - $this->firstColumnWidth - ($this->columns * $this->columnSpacing)) / ($this->columns - 1);
            
            $this->SetTextColor(50, 50, 50);
            $this->Ln(12);
            $this->SetFont($this->font, 'B', 9);

            // ISBN
            $this->Cell(1, 10, '', 0, 0, 'L', 0);
            $this->Cell($this->firstColumnWidth, 10, iconv("UTF-8", "ISO-8859-1", $this->lang['isbn']), 0, 0, 'L', 0);

            // Editore
            $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
            $this->Cell($width_other, 10, iconv("UTF-8", "ISO-8859-1", $this->lang['editor']), 0, 0, 'C', 0);
            
            // Autore
            $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
            $this->Cell($width_other, 10, iconv("UTF-8", "ISO-8859-1", $this->lang['author']), 0, 0, 'C', 0);

          /*  if (isset($this->vatField)) {
                // iva
                $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
                $this->Cell($width_other, 10, iconv("UTF-8", "ISO-8859-1", $this->lang['vat']), 0, 0, 'C', 0);
            }*/

            // Descrizione
            $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
            $this->Cell(90, 10, iconv("UTF-8", "ISO-8859-1", $this->br2nl($this->lang['description'])), 0, 0, 'C', 0);

            // prezzo
            $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
            $this->Cell($width_other, 10, iconv("UTF-8", "ISO-8859-1", strtoupper($this->lang['price'])), 0, 0, 'C', 0);

            //if (isset($this->discountField)) {
            // Quantita'
                $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
                $this->Cell($width_other, 10, iconv("UTF-8", "ISO-8859-1", $this->lang['qty']), 0, 0, 'C', 0);
           // }
            
            // totale
            $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
            $this->Cell($width_other, 10, iconv("UTF-8", "ISO-8859-1", strtoupper($this->lang['total'])), 0, 0, 'C', 0);

            $this->Ln();
            $this->SetLineWidth(0.3);
            $this->SetDrawColor($this->color[0], $this->color[1], $this->color[2]);
            $this->Line($this->margins['l'], $this->GetY(), $this->document['w'] - $this->margins['r'], $this->GetY());
            $this->Ln(2);
        } 
        else 
        {
            $this->Ln(12);
        }
    }

    public function Body() {
        $width_other = ($this->document['w'] - $this->margins['l'] - $this->margins['r'] - $this->firstColumnWidth - ($this->columns * $this->columnSpacing)) / ($this->columns - 1);
        
        
        $cellHeight = 8;
        $bgcolor = (1 - $this->columnOpacity) * 255;
        if ($this->items) {
            foreach ($this->items as $item) {

                if ($item['description']) 
                {
                    //Precalculate height
                    $calculateHeight = new Pdf();
                    $calculateHeight->addPage();
                    $calculateHeight->setXY(0, 0);
                    $calculateHeight->SetFont($this->font, '', 7);
                    $calculateHeight->MultiCell($this->firstColumnWidth, 3, iconv("UTF-8", "ISO-8859-1",$item['description']), 0, 'L', 1);
                    $descriptionHeight = $calculateHeight->getY() + $cellHeight + 2;
                    $pageHeight = $this->document['h'] - $this->GetY() - $this->margins['t'] - $this->margins['t'];
                    if ($pageHeight < 35) {
                        $this->AddPage();
                    }
                }
                $cHeight = $cellHeight;
                $this->SetFont($this->font, 'b', 8);
                $this->SetTextColor(50, 50, 50);
                $this->SetFillColor($bgcolor, $bgcolor, $bgcolor);

                // isbn
                $this->Cell(1, $cHeight, '', 0, 0, 'L', 1);
                $x = $this->GetX();
                $this->Cell($this->firstColumnWidth, $cHeight, iconv("UTF-8", "ISO-8859-1", $item['isbn']), 0, 0, 'L', 1);
               /* if ($item['description']) {
                    $resetX = $this->GetX();
                    $resetY = $this->GetY();
                    $this->SetTextColor(120, 120, 120);
                    $this->SetXY($x, $this->GetY() + 8);
                    $this->SetFont($this->font, '', 7);
                    $this->MultiCell($this->firstColumnWidth, 3, iconv("UTF-8", "ISO-8859-1", $item['description']), 0, 'L', 1);
                    
                    //Calculate Height
                    $newY = $this->GetY();
                    $cHeight = $newY - $resetY + 2;
                    //Make our spacer cell the same height
                    $this->SetXY($x - 1, $resetY);
                    $this->Cell(1, $cHeight, '', 0, 0, 'L', 1);
                    //Draw empty cell
                    $this->SetXY($x, $newY);
                    $this->Cell($this->firstColumnWidth, 2, '', 0, 0, 'L', 1);
                    $this->SetXY($resetX, $resetY);
                }*/
                
                $this->SetTextColor(50, 50, 50);
                $this->SetFont($this->font, '', 8);

                // Editore
                $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
                $this->Cell(20, $cHeight, $item['editor'], 0, 0, 'C', 1);
                
               /*if (isset($this->vatField)) {
                    // Iva
                    $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
                    if (isset($item['vat'])) 
                    {
                        $this->Cell($width_other, $cHeight, iconv('UTF-8', 'windows-1252', $item['vat']), 0, 0, 'C', 1);
                    } 
                    else 
                    {
                        $this->Cell($width_other, $cHeight, '', 0, 0, 'C', 1);
                    }
                }*/
                
                // autore
                $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
                $this->Cell($width_other, $cHeight, $item['author'], 0, 0, 'C', 1);
                
                // descrizione
                $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
                $this->Cell($width_other, $cHeight, $item['description'], 0, 0, 'C', 1);
                

                // Prezzo
                $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
                $this->Cell($width_other, $cHeight, iconv('UTF-8', 'windows-1252', $this->currency . ' ' . number_format($item['price'], 2, $this->referenceformat[0], $this->referenceformat[1])), 0, 0, 'C', 1);

                /*if (isset($this->discountField)) {
                    // Sconto
                    $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
                    if (isset($item['discount'])) {
                        $this->Cell($width_other, $cHeight, iconv('UTF-8', 'windows-1252', $item['discount']), 0, 0, 'C', 1);
                    } else {
                        $this->Cell($width_other, $cHeight, '', 0, 0, 'C', 1);
                    }
                }*/
               
                // Quantita'
                $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
                $this->Cell($width_other, $cHeight, $item['qty'], 0, 0, 'C', 1);

                // Totale
                $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
                $this->Cell($width_other, $cHeight, iconv('UTF-8', 'windows-1252', $this->currency . ' ' . number_format($item['total'], 2, $this->referenceformat[0], $this->referenceformat[1])), 0, 0, 'C', 1);

                // $this->Ln();
                // $this->Ln($this->columnSpacing);
                
                $this->Ln();
                $this->Ln($this->columnSpacing);
                
                
            }
        }
        $badgeX = $this->getX();
        $badgeY = $this->getY();

        //Add totals
        if ($this->totals) {
            foreach ($this->totals as $total) {
                $this->SetTextColor(50, 50, 50);
                $this->SetFillColor($bgcolor, $bgcolor, $bgcolor);
                $this->Cell(1 + $this->firstColumnWidth, $cellHeight, '', 0, 0, 'L', 0);
                for ($i = 0; $i < $this->columns - 3; $i++) {
                    $this->Cell($width_other, $cellHeight, '', 0, 0, 'L', 0);
                    $this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
                }
                $this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
                if ($total['colored']) {
                    $this->SetTextColor(255, 255, 255);
                    $this->SetFillColor($this->color[0], $this->color[1], $this->color[2]);
                }
                $this->SetFont($this->font, 'b', 8);
                $this->Cell(1, $cellHeight, '', 0, 0, 'L', 1);
                $this->Cell($width_other - 1, $cellHeight, iconv('UTF-8', 'windows-1252', $total['name']), 0, 0, 'L', 1);
                $this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
                $this->SetFont($this->font, 'b', 8);
                $this->SetFillColor($bgcolor, $bgcolor, $bgcolor);
                if ($total['colored']) {
                    $this->SetTextColor(255, 255, 255);
                    $this->SetFillColor($this->color[0], $this->color[1], $this->color[2]);
                }
                $this->Cell($width_other, $cellHeight, iconv('UTF-8', 'windows-1252', $total['value']), 0, 0, 'C', 1);
                $this->Ln();
                $this->Ln($this->columnSpacing);
            }
        }
        
        $this->productsEnded = true;
        $this->Ln();
        $this->Ln(3);

/*
        //Badge
        if ($this->badge) {
            $badge = ' ' . strtoupper($this->badge) . ' ';
            $resetX = $this->getX();
            $resetY = $this->getY();
            $this->setXY($badgeX, $badgeY + 15);
            $this->SetLineWidth(0.4);
            $this->SetDrawColor($this->color[0], $this->color[1], $this->color[2]);
            $this->setTextColor($this->color[0], $this->color[1], $this->color[2]);
            $this->SetFont($this->font, 'b', 15);
            $this->Rotate(10, $this->getX(), $this->getY());
            $this->Rect($this->GetX(), $this->GetY(), $this->GetStringWidth($badge) + 2, 10);
            $this->Write(10, $badge);
            $this->Rotate(0);
            if ($resetY > $this->getY() + 20) {
                $this->setXY($resetX, $resetY);
            } else {
                $this->Ln(18);
            }
        }

*/        
        
          $this->SetFont($this->font, '', 11);
             $this->SetTextColor(50, 50, 50);
        
                        //$this->setTextColor($this->color[0], $this->color[1], $this->color[2]);
                                $lineheight = 5;
                                $width = ($this->document['w']-$this->margins['l']-$this->margins['r'])/2;
				$this->Cell($width,$lineheight,($this->dataFirma),1,0,'L');
				$this->Cell(0,$lineheight,($this->lang['to']),1,0,'L');
				$this->Ln(4);
				$this->SetLineWidth(0.1);
				//$this->Line($this->margins['l'], $this->GetY(),$this->margins['l']+$width-30, $this->GetY());
				//$this->Line($this->margins['l']+$width, $this->GetY(),$this->margins['l']+$width+$width, $this->GetY());
	
				
                         $this->Line($this->margins['l']+$width+33, $this->GetY(),$this->margins['l']+$width+$width, $this->GetY());
	
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        //Add information
        foreach ($this->addText as $text) {
            if ($text[0] == 'title') {
                
                $this->SetFont($this->font, 'b', 9);
                $this->SetTextColor(50, 50, 50);
                $this->Cell(0, 10, iconv("UTF-8", "ISO-8859-1", "ccccc" .strtoupper($text[1])), 0, 0, 'L', 0);
                $this->Ln();
                $this->SetLineWidth(0.3);
                $this->SetDrawColor($this->color[0], $this->color[1], $this->color[2]);
                $this->Line($this->margins['l'], $this->GetY(), $this->document['w'] - $this->margins['r'], $this->GetY());
                $this->Ln(4);
            }
            if ($text[0] == 'paragraph') {
                $this->SetTextColor(80, 80, 80);
                $this->SetFont($this->font, '', 8);
                $this->MultiCell(0, 4, iconv("UTF-8", "ISO-8859-1", $text[1]), 0, 'L', 0);
                $this->Ln(4);
            }
        }
    }

    public function Footer() {
        $this->SetY(-$this->margins['t']);
        $this->SetFont($this->font, '', 8);
        $this->SetTextColor(50, 50, 50);
        $this->Cell(0, 10, $this->footernote, 0, 0, 'L');
        $this->Cell(0, 10, $this->lang['page'] . ' ' . $this->PageNo() . ' ' . $this->lang['page_of'] . ' {nb}', 0, 0, 'R');
    }

}

?>