<?php

namespace App\Http\Classes;

use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use PDF;
use TCPDF_FONTS;

use Illuminate\Support\Facades\Storage;

class reportheader
{
	private $companysetup;
	private $coreFunctions;
	private $othersClass;

	public function __construct()
	{
		$this->companysetup = new companysetup;
		$this->coreFunctions = new coreFunctions;
		$this->othersClass = new othersClass;
	} //end construct

	public function getHeader($params)
	{
		$center = $params['params']['center'];
		switch ($params['params']['companyid']) {
			case 10: //afti
				switch ($params['params']['doc']) {
					case 'AC':
						PDF::Image($this->companysetup->getlogopath($params['params']) . 'aftilogo.png', '35', '30', 60, 50);
						break;
					default:
						PDF::Image($this->companysetup->getlogopath($params['params']) . 'qslogo.png', '30', '', 220, 50);
						break;
				}
				break;
			case 8: //maxipro
				switch ($params['params']['doc']) {
					case 'JO':
					case 'PO':
						PDF::Image($this->companysetup->getlogopath($params['params']) . 'mdc.jpg', 25, 40, 100, 55);
						PDF::Image($this->companysetup->getlogopath($params['params']) . 'mdcbarcode2.jpg', 455, 40, 100, 55);
						break;
					case 'PB':
						PDF::Image($this->companysetup->getlogopath($params['params']) . 'mdc.jpg', 80, 30, 125, 70);
						PDF::Image($this->companysetup->getlogopath($params['params']) . 'mdcbarcode2.jpg', 975, 30, 120, 65);

						break;
					default:
						$qry = "select name,address,tel,email from center where code = '" . $center . "'";
						$headerdata = $this->coreFunctions->opentable($qry);
						$qrbarcode = 0;
						switch ($params['params']['doc']) {
							case 'BR':
								$mdc = 80;
								$tuvpos = 775;
								$qrbarcode = 840;

								break;
							case 'BA':
								$mdc = 80;
								$tuvpos = 785;
								$qrbarcode = 850;
								break;
							case 'PM':
							case 'BL':
								$mdc = 80;
								$tuvpos = 975; //1020
								$qrbarcode = 1040;

								break;
							default:
								$mdc = 60; //60
								$tuvpos = 615; //605
								$qrbarcode = 670; //670

								break;
						}

						$font = "";
						$fonthead = "";
						$fontbold = "";
						$fontsize = 11;
						if (Storage::disk('sbcpath')->exists('/fonts/ARIAL.ttf')) {
							$font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIAL.ttf');
							$fonthead = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/arialblk.ttf');
							$fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/ARIALNB.TTF');
						}


						PDF::SetFont($fonthead, '', 17);
						PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
						PDF::SetFont($font, '', 5);
						PDF::MultiCell(0, 0, '', '');
						PDF::SetFont($fontbold, '', 10);

						PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel, '', 'C');
						PDF::MultiCell(0, 0, $headerdata[0]->email . "\n\n", '', 'C');

						$style = ['width' => 3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [255, 0, 0]];
						PDF::SetLineStyle($style);

						PDF::Line(PDF_MARGIN_LEFT, PDF::getY(), PDF::getPageWidth() - PDF_MARGIN_LEFT, PDF::getY());

						PDF::Image($this->companysetup->getlogopath($params['params']) . 'mdc.jpg', $mdc, 30, 125, 70);
						PDF::Image($this->companysetup->getlogopath($params['params']) . 'mdcbarcode2.jpg', $tuvpos, 30, 120, 65);

						$style = ['width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [0, 0, 0]];
						PDF::SetLineStyle($style);

						break;
				}
				break;
			case 46: //MSSE
				$qry = "select name,address,tel from center where code = '" . $center . "'";
				$headerdata = $this->coreFunctions->opentable($qry);
				$current_timestamp = $this->othersClass->getCurrentTimeStamp();

				$fontsize = 11;
				$font = "courier";
				$fontbold = "courier";

				if (Storage::disk('sbcpath')->exists('/fonts/Courier bold.ttf')) {
					$fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/Courier bold.ttf');
				}

				PDF::SetFont($fontbold, '', 14);
				PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
				PDF::SetFont($fontbold, '', 13);
				PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');

				break;

			case 61: //bytesized
				if (isset($params['params']['logoonly'])) {
					if ($params['params']['logoonly']) {
						PDF::SetFont("", '', 14);
						PDF::MultiCell(0, 0, '', '', 'C');
						PDF::SetFont("", '', 13);
						PDF::MultiCell(0, 0, '', '', 'C');
					} else {
						goto defaultCompanyHeader;
					}
				} else {
					goto defaultCompanyHeader;
				}
				break;

			default: //STANDARD - company 0
				defaultCompanyHeader:
				switch ($params['params']['doc']) {
					case 'CV':
					case 'PV':
						switch ($params['params']['dataparams']['reporttype']) {
							case 2:
								PDF::Image(public_path() . '/images/afti/birlogo.png', '310', '10', 55, 55);
								PDF::Image(public_path() . '/images/afti/bir2307.png', '12', '80', 103, 43);
								PDF::Image(public_path() . '/images/afti/birbarcode.png', '595', '80', 190, 43);
								break;

							default:
								goto defaultHeader;
								break;
						}
						break;

					default:
						defaultHeader:
						$qry = "select name,address,tel from center where code = '" . $center . "'";
						$headerdata = $this->coreFunctions->opentable($qry);
						$current_timestamp = $this->othersClass->getCurrentTimeStamp();

						$font = "";
						$fontbold = "";
						$fontsize = 11;

						switch ($params['params']['companyid']) {
							case 56: //homeworks
								if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
									$font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
									$fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
								}
								break;
							default:
								if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
									$font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
									$fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
								}
								break;
						}

						PDF::SetFont($fontbold, '', 14);
						PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
						PDF::SetFont($font, '', 13);
						PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');
						break;
				}


				break;
		}
	}
}
