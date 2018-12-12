<?php
	namespace Dplus\PrintFormatters;

	use Dplus\ScreenFormatters\ScreenMaker;
	use Dplus\ScreenFormatters\ScreenMakerFormatter;
	use Dplus\Content\HTMLWriter;
	use Dplus\Content\Table;
	use Picqer\Barcode\BarcodeGeneratorPNG;

	class ShortPayFormatter extends ScreenMakerFormatter {
		protected $screentype = 'grid';
		protected $code = 'shortpay';
		protected $title = 'Short Pay';
		protected $datafilename = 'shortpay'; // shortpay.json
		protected $testprefix = 'shortpay';
		protected $datasections = array();

		/**
		 * Returns the title for the document screen
		 * @return string Document Title
		 */
		public function get_doctitle() {
			return $this->debug ? "$this->title DEBUG" : $this->title . ' #'.$this->sessionID;
		}

		/**
		 * Returns the HTML content needed to generate the Print Screen
		 * @return string HTML
		 */
		public function generate_screen() {
			$bootstrap = new HTMLWriter();
			$this->generate_tableblueprint();
			$content = $this->generate_documentheader();
			$content .= $bootstrap->open('div','class=row');
			for ($i = 1; $i < 3; $i++) {
				$content .= $bootstrap->div('class=col-xs-4', $this->generate_headersection($i));
			}
			$content .= $bootstrap->close('div');
			$content .= $this->generate_reasonsection();
			$content .= $this->generate_claimsection();
			return $content;
		}

		/**
		 * Returns the Header Portion of the RGA Document
		 * Includes Short Pay Number, Company Logo, Company Address
		 * @return string HTML Content
		 */
		protected function generate_documentheader() {
			$bootstrap = new HTMLWriter();
			$barcoder_png = new BarcodeGeneratorPNG();
			$barcode_base64 = base64_encode($barcoder_png->getBarcode($this->json['Short Pay Number'], $barcoder_png::TYPE_CODE_128));
			$companydata = $this->json['data'];
			
			$content = $bootstrap->h2('class=text-center', strtoupper($this->title));
			$content .= $bootstrap->p('class=strong text-center', $bootstrap->small('', "Please include a copy of this form with remittance"));
			$content .= $bootstrap->p('class=strong text-center', $bootstrap->b('', "Void 60 days after date issued"));
			$content .= $bootstrap->open('div', 'class=row');
				$content .= $bootstrap->open('div', 'class=col-xs-6');
					$content .= $bootstrap->h4('', 'Short Pay #'. $this->json['Short Pay Number']);
					$content .= $bootstrap->div('', $bootstrap->img("src=data:image/png;base64,$barcode_base64|class=img-responsive|alt=Short Pay Number Barcode"));
					$content .= $bootstrap->br();
					
				$content .= $bootstrap->close('div');
			$content .= $bootstrap->close('div');
			
			return $content;
		}

		/**
		 * Returns the Header information of an RGA
		 * @param  int    $number Which Section Number 1 - 6
		 * @return string         HTML Table for that section
		 */
		protected function generate_headersection($number = 1) {
			$bootstrap = new HTMLWriter();
			$tb = new Table('class=table table-condensed table-striped');
			
			for ($i = 1; $i < sizeof($this->tableblueprint['sections']["$number"]) + 1; $i++) {
				if (isset($this->tableblueprint['sections']["$number"]["$i"])) {
					$column = $this->tableblueprint['sections']["$number"]["$i"];
					$tb->tr();
					$tb->td('', $bootstrap->b('', $column['label']));
					$celldata = ScreenMaker::generate_formattedcelldata($this->json['data'], $column);
					$tb->td('', $celldata);
				}
			}
			return $tb->close();
		}
		
		/**
		 * Returns the HTML for the terms section of the document
		 * @return string HTML Content
		 */
		protected function generate_reasonsection() {
			$bootstrap = new HTMLWriter();
			$tb = new Table('class=table table-condensed table-striped|id=reason-table');
			$tb->tr();
				$tb->td('', $bootstrap->b('', "Reason"));
				$tb->td('', $this->json['data']['Item Description 1']);
			if (trim($this->json['data']['Item Description 1']) == "SHIPPING-CARRIER DAMAGE/LOSS") {
				$tb->tr();
					$tb->td('', '');
					$tb->td('', $this->json['data']["Carrier Damage/Loss"]);
				$tb->tr();
					$tb->td('', $bootstrap->b('', "Item ID"));
					$tb->td('', $this->json['data']["Carrier D/L Item ID"]);
				$tb->tr();
					$tb->td('', $bootstrap->b('', "Quantity"));
					$tb->td('', $this->json['data']["Carrier D/L Quantity"]);
			}
			$tb->tr();
				$tb->td('', $bootstrap->b('', 'Comments (Required)'));
				$tb->td();
			$tb->tr();
				$tb->td('', "&nbsp;");
				$tb->td();
			$tb->tr();
				$tb->td('', "&nbsp;");
				$tb->td();
			return $tb->close();
		}
		
		protected function generate_claimsection() {
			$bootstrap = new HTMLWriter();
			$tb = new Table('class=table table-condensed table-striped|id=reason-table');
			$tb->tr();
				$tb->td('', $bootstrap->b('', $this->formatter['columns']["Carrier Claim Filed"]['label']));
				$tb->td('', $this->json['data']["Carrier Claim Filed"]);
				
				$tb->td('', $bootstrap->b('', $this->formatter['columns']["Original Ship Via Desc"]['label']));
				$tb->td('', $this->json['data']["Original Ship Via Desc"] . " (".$this->json['data']["Original Ship Via Code"] .")");
				
				$tb->td('', $bootstrap->b('', $this->formatter['columns']["Salesperson Name"]['label']));
				$tb->td('', $this->json['data']["Salesperson Name"]);
			$tb->tr();
				$tb->td('', '');
				$tb->td('', '');
				
				$tb->td('', '');
				$tb->td('', '');
				
				$tb->td('', $bootstrap->b('', $this->formatter['columns']["Manager Initials"]['label']));
				$tb->td('', $this->json['data']["Manager Initials"]);
			return $tb->close();
		}
		
		/**
		 * Generates the table blueprint
		 * This page divides the Item Page Screen into 4 sections / columns
		 * @return void
		 */
		protected function generate_tableblueprint() {
			$table = array(
				'sections' => array(
					'1' => array(),
					'2' => array()
				)
			);

			for ($i = 1; $i < sizeof($table['sections']) + 1; $i++) {
				foreach (array_keys($this->formatter['columns']) as $column) {
					if ($this->formatter['columns'][$column]['column'] == $i) {
						$col = array(
							'id'             => $column,
							'label'          => $this->formatter['columns'][$column]['label'],
							'line'           => $this->formatter['columns'][$column]['line'],
							'column'         => $this->formatter['columns'][$column]['column'],
							'type'           => $this->fields[$column]['type'],
							'col-length'     => $this->formatter['columns'][$column]['col-length'],
							'before-decimal' => $this->formatter['columns'][$column]['before-decimal'],
							'after-decimal'  => $this->formatter['columns'][$column]['after-decimal'],
							'date-format'    => $this->formatter['columns'][$column]['date-format'],
							'percent'        => $this->formatter['columns'][$column]['percent'],
							'input'          => $this->formatter['columns'][$column]['input'],
							'data-justify'   => $this->formatter['columns'][$column]['data-justify'],
							'label-justify'  => $this->formatter['columns'][$column]['label-justify']
						);
						$table['sections'][$i][$this->formatter['columns'][$column]['line']] = $col;
						$table['colcount'] = $col['column'] > $table['colcount'] ? $col['column'] : $table['colcount'];
					}
				}
			}
			$this->tableblueprint = $table;
		}
	}
