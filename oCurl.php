$timeout = 5;
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

				// SET HEADERS
				$headers = array();
				$headers[] = "Expect: ";
				curl_setopt($ch, CURLINFO_HEADER_OUT, true);


				if( defined('__OBRAY_REMOTE_HOSTS__') && defined('__OBRAY_TOKEN__') && in_array($components['host'],unserialize(__OBRAY_REMOTE_HOSTS__)) ){ $headers[] = 'Obray-Token: '.__OBRAY_TOKEN__; }
				if( !empty($params['http_headers']) ){ $headers = $params['http_headers']; unset($params["http_headers"]); }
				if( !empty($params['http_content_type']) ){ $headers[] = 'Content-type: '.$params['http_content_type']; $content_type = $params['http_content_type']; unset($params['http_content_type']);  }
				if( !empty($params['http_accept']) ){ $headers[] = 'Accept: '.$params['http_accept']; unset($params['http_accept']); }
				if( !empty($params['http_username']) && !empty($params['http_password']) ){ curl_setopt($ch, CURLOPT_USERPWD, $params['http_username'].":".$params['http_password']); unset($params['http_username']); unset($params['http_password']); }
				if( !empty($params['http_username']) && empty($params['http_password']) ){ curl_setopt($ch, CURLOPT_USERPWD, $params['http_username'].":"); unset($params['http_username']); }
				if( !empty($params['http_raw']) ){ $show_raw_data = TRUE; unset($params['http_raw']); }
				if( !empty($params['http_debug']) ){ $debug = TRUE; unset($params["http_debug"]); } else { $debug = FALSE; }
				if( !empty($params['http_user_agent']) ){ curl_setopt($ch,CURLOPT_USERAGENT,$params["http_user_agent"]); unset($params["http_user_agent"]); }

				if( (!empty($this->params) && empty($params['http_method'])) || (!empty($params['http_method']) && $params['http_method'] == 'post') ){
					unset($params["http_method"]);
					if( count($params) == 1 && !empty($params["body"]) ){
						curl_setopt($ch, CURLOPT_POST, 1);
						curl_setopt($ch, CURLOPT_POSTFIELDS, $params["body"]);
					} else {
						if( !empty($content_type) && $content_type == "application/json" ){
							curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
							$json = json_encode($params);
							$headers[] = 'Content-Length: '.strlen($json);
							curl_setopt($ch, CURLOPT_POST, 1);
							curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
						} else {
							if( $debug ){
								$this->console("\n\nPost Fields\n");
								$this->console("Count: ".count($params)."\n");
								$this->console($params);
							}
							curl_setopt($ch, CURLOPT_POST, count($params));
							curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
						}
					}

				} else {
					if( !empty($params["http_method"]) ){ unset($params["http_method"]); }
					if( !empty($components["query"]) ){
						$path.= "?" . $components["query"];
					}
					if( $debug ){ $this->console($path); }
					curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
				}

				if( $debug ){ $this->console($params); }
				
				if( !empty($headers) ){ 
					if( $debug ){
						$this->console("*****HEADERS*****");
						$this->console($headers);
					}
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
				} else {
					if( $debug ){
						$this->console("NO HEADERS SET!\n");
					}
				}
				curl_setopt($ch, CURLOPT_URL, $path);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
				curl_setopt($ch, CURLOPT_TIMEOUT, 400); //timeout in seconds
				$this->data = curl_exec($ch);

				if( $debug ){
					$this->console($this->data);
				}
				
				$headers = curl_getinfo($ch, CURLINFO_HEADER_OUT);
				$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
				$info = curl_getinfo( $ch );
				$data = json_decode($this->data);
				
				$info["http_code"] =  intval($info["http_code"]);

				if( !( $info["http_code"] >= 200 && $info["http_code"] < 300)  ){

					$this->data = array();
					//echo "HTTP CODE IS NOT 200";
					if( !empty($data->Message) ){
						$this->throwError($data->Message,$info["http_code"]);
					} else if ( !empty($data->error) ){
						$this->throwError( $data->error );
					} else if ( !empty($data->errors) ){
						$this->throwError("");
						$this->errors = $data->errors;
					} else {
						$this->throwError("An error has occurred with no message.",$info["http_code"]);
					}
					return $this;
				} else {

					if( !empty($data) ){ $this->data = $data; } else { return $this; }

					if( !empty($this->data) ){
						if( isSet($this->data->errors) ){ $this->errors = $this->data->errors; }
						if( isSet($this->data->html) ){ $this->html = $this->data->html; }
						if( isSet($this->data->data) && empty($show_raw_data) ){ $this->data = $this->data->data; }
					}
				}