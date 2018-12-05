<?php
	namespace Dplus\PrintFormatters;

	use Dplus\Base\ScreenMaker;

	/**
	 * Factory to load all the Screen Formatters
	 */
	class PrintFormatterFactory {
		use \Dplus\Base\ThrowErrorTrait;
        
        /**
         * Company Number
         * @var int
         */
		protected $companynbr;
		
		/**
		 * Formatter Array with code as the key and the NAme as the value
		 * @var array
		 */
		protected $formatters = array(
			'return-goods-authorization' => 'ReturnGoodsAuthorizationFormatter',
			'shortpay'                   => 'ShortPayFormatter'
		);
		
		/**
		 * Aliases for formatter codes
		 * @var string
		 */
		protected $aliases = array(
			'rga' => 'return-goods-authorization'
		);
		
		/**
		 * Constructor
		 * @param string $sessionID Session Identifier
		 */
		public function __construct($sessionID) {
			$this->sessionID = $sessionID;
		}
		
		/**
		 * Returns Screen formatter object of the type provided
		 * @param  string            $type    Formatter Type
		 * @return ScreenMaker                Screen object
		 */
		public function get_formatter($type) {
			if (in_array($type, array_keys($this->formatters))) {
				$class = __NAMESPACE__."\\".$this->formatters[$type];
				return new $class($this->sessionID);
			} elseif (in_array($type, array_keys($this->aliases))) { 
				$class = __NAMESPACE__."\\".$this->formatters[$this->aliases[$type]];
				return new $class($this->sessionID);
			} else {
				$this->error("Formatter $type does not exist");
				return false;
			}
		}
		
		public function get_formatter_from_filename($filename) {
			$filearray = explode('-', str_replace('.json', '', $filename));
			$type = $filearray[1];
			$fileID = $filearray[0];
			$this->sessionID = $fileID;
			
			return $this->get_formatter($type);
		}
	} 
