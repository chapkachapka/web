<?
use function Safe\ini_get;
use function Safe\preg_match;

class HttpInput{
	public static function RequestMethod(): int{
		$method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];

		switch($method){
			case 'POST':
				return HTTP_POST;
			case 'PUT':
				return HTTP_PUT;
			case 'DELETE':
				return HTTP_DELETE;
			case 'PATCH':
				return HTTP_PATCH;
			case 'HEAD':
				return HTTP_HEAD;
		}

		return HTTP_GET;
	}

	public static function GetMaxPostSize(): int{ // bytes
		$post_max_size = ini_get('post_max_size');
		$unit = substr($post_max_size, -1);
		$size = (int) substr($post_max_size, 0, -1);

		return match ($unit){
			'g', 'G' => $size * 1024 * 1024 * 1024,
			'm', 'M' => $size * 1024 * 1024,
			'k', 'K' => $size * 1024,
			default => $size
		};
	}

	public static function IsRequestTooLarge(): bool{
		if(empty($_POST) || empty($_FILES)){
			if($_SERVER['CONTENT_LENGTH'] > self::GetMaxPostSize()){
				return true;
			}
		}

		return false;
	}

	public static function RequestType(): int{
		return preg_match('/\btext\/html\b/ius', $_SERVER['HTTP_ACCEPT'] ?? '') ? WEB : REST;
	}

	public static function Str(string $type, string $variable, bool $allowEmptyString = false): ?string{
		$var = self::GetHttpVar($variable, HTTP_VAR_STR, $type);

		if(is_array($var)){
			return null;
		}

		if(!$allowEmptyString && $var == ''){
			return null;
		}

		return $var;
	}

	public static function Int(string $type, string $variable): ?int{
		return self::GetHttpVar($variable, HTTP_VAR_INT, $type);
	}

	public static function Bool(string $type, string $variable): ?bool{
		return self::GetHttpVar($variable, HTTP_VAR_BOOL, $type);
	}

	public static function Dec(string $type, string $variable): ?float{
		return self::GetHttpVar($variable, HTTP_VAR_DEC, $type);
	}

	/**
	* @param string $variable
	* @return array<string>
	*/
	public static function GetArray(string $variable): ?array{
		return self::GetHttpVar($variable, HTTP_VAR_ARRAY, GET);
	}

	private static function GetHttpVar(string $variable, int $type, string $set): mixed{
		$vars = [];

		switch($set){
			case GET:
				$vars = $_GET;
				break;
			case POST:
				$vars = $_POST;
				break;
			case COOKIE:
				$vars = $_COOKIE;
				break;
			case SESSION:
				$vars = $_SESSION;
				break;
		}

		if(isset($vars[$variable])){
			if($type == HTTP_VAR_ARRAY && is_array($vars[$variable])){
				// We asked for an array, and we got one
				return $vars[$variable];
			}
			elseif($type !== HTTP_VAR_ARRAY && is_array($vars[$variable])){
				// We asked for not an array, but we got an array
				return null;
			}
			else{
				$var = trim($vars[$variable]);
			}

			switch($type){
				case HTTP_VAR_STR:
					return $var;
				case HTTP_VAR_INT:
					// Can't use ctype_digit because we may want negative integers
					if(is_numeric($var) && mb_strpos($var, '.') === false){
						try{
							return intval($var);
						}
						catch(Exception){
							return null;
						}
					}
					break;
				case HTTP_VAR_BOOL:
					if($var === '0' || strtolower($var) == 'false' || strtolower($var) == 'off'){
						return false;
					}
					else{
						return true;
					}
				case HTTP_VAR_DEC:
					if(is_numeric($var)){
						try{
							return floatval($var);
						}
						catch(Exception){
							return null;
						}
					}
					break;
			}
		}

		return null;
	}
}
