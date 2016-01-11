<?php
/**
* NOTA SOBRE LA LICENCIA DE USO DEL SOFTWARE
* 
* El uso de este software está sujeto a las Condiciones de uso de software que
* se incluyen en el paquete en el documento "Aviso Legal.pdf". También puede
* obtener una copia en la siguiente url:
* http://www.redsys.es/wps/portal/redsys/publica/areadeserviciosweb/descargaDeDocumentacionYEjecutables
* 
* Redsys es titular de todos los derechos de propiedad intelectual e industrial
* del software.
* 
* Quedan expresamente prohibidas la reproducción, la distribución y la
* comunicación pública, incluida su modalidad de puesta a disposición con fines
* distintos a los descritos en las Condiciones de uso.
* 
* Redsys se reserva la posibilidad de ejercer las acciones legales que le
* correspondan para hacer valer sus derechos frente a cualquier infracción de
* los derechos de propiedad intelectual y/o industrial.
* 
* Redsys Servicios de Procesamiento, S.L., CIF B85955367
*/

if(!function_exists("escribirLog")) {
	require_once('apiRedsys/redsysLibrary.php');
}
if(!class_exists("RedsysAPI")) {
	require_once('apiRedsys/apiRedsysFinal.php');
}

class Excellenceredsys_Redsys_IndexController extends Mage_Core_Controller_Front_Action {        
	
	public function redirectAction() {
		
		//Obtenemos los valores de la configuración del módulo
		$entorno =Mage::getStoreConfig('payment/redsys/entorno',Mage::app()->getStore());
		$nombre =Mage::getStoreConfig('payment/redsys/nombre',Mage::app()->getStore());
		$codigo =Mage::getStoreConfig('payment/redsys/num',Mage::app()->getStore());
		$clave256 =Mage::getStoreConfig('payment/redsys/clave256',Mage::app()->getStore());
		$terminal =Mage::getStoreConfig('payment/redsys/terminal',Mage::app()->getStore());
		$moneda =Mage::getStoreConfig('payment/redsys/moneda',Mage::app()->getStore());
		$trans =Mage::getStoreConfig('payment/redsys/trans',Mage::app()->getStore());
		$notif =Mage::getStoreConfig('payment/redsys/notif',Mage::app()->getStore());
		$ssl =Mage::getStoreConfig('payment/redsys/ssl',Mage::app()->getStore());
		$error =Mage::getStoreConfig('payment/redsys/error',Mage::app()->getStore());
		$idiomas =Mage::getStoreConfig('payment/redsys/idiomas',Mage::app()->getStore());
		$tipopago =Mage::getStoreConfig('payment/redsys/tipopago',Mage::app()->getStore());
		$correo =Mage::getStoreConfig('payment/redsys/correo',Mage::app()->getStore());
		$mensaje =Mage::getStoreConfig('payment/redsys/mensaje',Mage::app()->getStore());
		
		//Obtenemos datos del pedido  
		$_order = new Mage_Sales_Model_Order();
		$orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
		$_order->loadByIncrementId($orderId);

		//Actualizamos estado del pedido a "pendiente"
		$state = 'new';
		$status = 'pending';
		$comment = 'Redsys ha actualizado el estado del pedido con el valor "'.$status.'"';
		$isCustomerNotified = true;
		$_order->setState($state, $status, $comment, $isCustomerNotified);
		$_order->save();
		//Datos del cliente
		$customer = Mage::getSingleton('customer/session')->getCustomer(); 
		
		//Datos de los productos del pedido
		$productos = '';
		$items = $_order->getAllVisibleItems();
            foreach ($items as $itemId => $item)
            { $productos .= $item->getName(); 
			  $productos .="X".$item->getQtyToInvoice(); 
			  $productos .="/";}
				
		//Formateamos el precio total del pedido
		$transaction_amount = number_format($_order->getBaseGrandTotal(),2,'', '');

		
		//Establecemos los valores del cliente y el pedido 
		if(isset($_SESSION["P".$orderId])){
			$sec_pedido = $_SESSION["P".$orderId];
		} else {
			$sec_pedido = -1;
		}
		if ($sec_pedido < 9) {
			$_SESSION["P".$orderId]=++$sec_pedido;
		}
		$numpedido = str_pad($orderId.$sec_pedido, 12, "0", STR_PAD_LEFT); 
		$cantidad = (float)$transaction_amount;
		$titular = $customer->getFirstname()." ".$customer->getMastname()." ".$customer->getLastname()."/ Correo:".$customer->getEmail();  

		//Generamos el urlTienda -> respuesta ON-LINE que deberá ser la establecida bajo las pautas de WooCommerce
		if ($ssl=="0")
		$urltienda = Mage::getBaseUrl().'redsys/index/notify';
		else 
		$urltienda = Mage::getBaseUrl().'redsys/index/notify';

		// Obtenemos el valor de la config del idioma
		if($idiomas=="0"){
		$idioma_tpv="0";
		}
		else { 
		$idioma_web = substr(Mage::getStoreConfig('general/locale/code', Mage::app()->getStore()->getId()),0,2);
			switch ($idioma_web) {
				case 'es':
				$idioma_tpv='001';
				break;
				case 'en':
				$idioma_tpv='002';
				break;
				case 'ca':
				$idioma_tpv='003';
				break;
				case 'fr':
				$idioma_tpv='004';
				break;
				case 'de':
				$idioma_tpv='005';
				break;
				case 'nl':
				$idioma_tpv='006';
				break;
				case 'it':
				$idioma_tpv='007';
				break;
				case 'sv':
				$idioma_tpv='008';
				break;
				case 'pt':
				$idioma_tpv='009';
				break;
				case 'pl':
				$idioma_tpv='011';
				break;
				case 'gl':
				$idioma_tpv='012';
				break;
				case 'eu':
				$idioma_tpv='013';
				break;
				default:
				$idioma_tpv='002';
			}		
		}
		
		// Obtenemos el código ISO del tipo de moneda
		if($moneda=="0"){
			$moneda="978";
		} else { 
			$moneda="840";
		}
		// Obtenemos los tipos de pago permitidos 
		if($tipopago=="0"){
			$tipopago=" ";
		}
		else if($tipopago=="1"){ 
			$tipopago="C";
		}
		else {
			$tipopago="T";
		}

		$miObj = new RedsysAPI;
		$miObj->setParameter("DS_MERCHANT_AMOUNT",$cantidad);
		$miObj->setParameter("DS_MERCHANT_ORDER",strval($numpedido));
		$miObj->setParameter("DS_MERCHANT_MERCHANTCODE",$codigo);
		$miObj->setParameter("DS_MERCHANT_CURRENCY",$moneda);
		$miObj->setParameter("DS_MERCHANT_TRANSACTIONTYPE",$trans);
		$miObj->setParameter("DS_MERCHANT_TERMINAL",$terminal);
		$miObj->setParameter("DS_MERCHANT_MERCHANTURL",$urltienda);
		$miObj->setParameter("DS_MERCHANT_URLOK",$urltienda);
		$miObj->setParameter("DS_MERCHANT_URLKO",$urltienda);
		$miObj->setParameter("Ds_Merchant_ConsumerLanguage",$idioma_tpv);
		$miObj->setParameter("Ds_Merchant_ProductDescription",$productos);
		$miObj->setParameter("Ds_Merchant_Titular",$nombre);
		$miObj->setParameter("Ds_Merchant_MerchantData",sha1($urltienda));
		$miObj->setParameter("Ds_Merchant_MerchantName",$nombre);
		$miObj->setParameter("Ds_Merchant_PayMethods",$tipopago);
		$miObj->setParameter("Ds_Merchant_Module","magento_redsys_2.8.4");

		//Datos de configuración
		$version = getVersionClave();
		
		//Clave del comercio que se extrae de la configuración del comercio
		// Se generan los parámetros de la petición
		$request = "";
		$paramsBase64 = $miObj->createMerchantParameters();
		$signatureMac = $miObj->createMerchantSignature($clave256);

		// Obtenemos el valor de la consulta para saber el entorno del TPV.
		if($entorno=="1"){
		echo ('<form action="http://sis-d.redsys.es/sis/realizarPago/utf-8" method="post" id="redsys_form" name="redsys_form">');}
		else if($entorno=="2"){
		echo ('<form action="https://sis-i.redsys.es:25443/sis/realizarPago/utf-8" method="post" id="redsys_form" name="redsys_form">');}
		else if($entorno=="3"){
		echo ('<form action="https://sis-t.redsys.es:25443/sis/realizarPago/utf-8" method="post" id="redsys_form" name="redsys_form">');}	
		else{
		echo ('<form action="https://sis.redsys.es/sis/realizarPago/utf-8" method="post" id="redsys_form" name="redsys_form">');}	
		
		// Establecemos el valor de los input
		echo ('
				<input type="hidden" name="Ds_SignatureVersion" value="'.$version.'" />
				<input type="hidden" name="Ds_MerchantParameters" value="'.$paramsBase64.'" />
				<input type="hidden" name="Ds_Signature" value="'.$signatureMac.'" />
				</form>
			
				<h3> Cargando el TPV... Espere por favor. </h3>		
				
				<script type="text/javascript">
					document.redsys_form.submit();
				</script>'
				);

	}

	
    public function notifyAction()
    {
		//header( 'Content-Type:text/html; charset=UTF-8' );
		
		$idLog = generateIdLog();
		$logActivo =Mage::getStoreConfig('payment/redsys/logactivo',Mage::app()->getStore());
		$mantenerPedidoAnteError =Mage::getStoreConfig('payment/redsys/errorpedido',Mage::app()->getStore());
		$orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();

        if (!empty($_POST)) //URL RESP. ONLINE
        { 
			/** Recoger datos de respuesta **/
			$version     = $_POST["Ds_SignatureVersion"];
			$datos    = $_POST["Ds_MerchantParameters"];
			$firma_remota    = $_POST["Ds_Signature"];

			// Se crea Objeto
			$miObj = new RedsysAPI;
			
			/** Se decodifican los datos enviados y se carga el array de datos **/
			$decodec = $miObj->decodeMerchantParameters($datos);

			/** Clave **/
			$kc = Mage::getStoreConfig('payment/redsys/clave256',Mage::app()->getStore());
			
			/** Se calcula la firma **/
			$firma_local = $miObj->createMerchantSignatureNotif($kc,$datos);
			
			/** Extraer datos de la notificación **/
			$total     = $miObj->getParameter('Ds_Amount');
			$pedido    = $miObj->getParameter('Ds_Order');
			$codigo    = $miObj->getParameter('Ds_MerchantCode');
			$terminal  = $miObj->getParameter('Ds_Terminal');
			$moneda    = $miObj->getParameter('Ds_Currency');
			$respuesta = $miObj->getParameter('Ds_Response');
			$fecha	   = $miObj->getParameter('Ds_Date');
			$hora	   = $miObj->getParameter('Ds_Hour');
			$id_trans  = $miObj->getParameter('Ds_AuthorisationCode');
			$tipoTrans = $miObj->getParameter('Ds_TransactionType');

			// Recogemos los datos del comercio
			$codigoOrig = Mage::getStoreConfig('payment/redsys/num',Mage::app()->getStore());	
			$terminalOrig = Mage::getStoreConfig('payment/redsys/terminal',Mage::app()->getStore());
			$monedaOrig =Mage::getStoreConfig('payment/redsys/moneda',Mage::app()->getStore());
			$tipoTransOrig =Mage::getStoreConfig('payment/redsys/trans',Mage::app()->getStore());

			// Obtenemos el código ISO del tipo de moneda
			if($monedaOrig == "0"){
				$monedaOrig = "978";
			} else { 
				$monedaOrig = "840";
			}

			// Inicializamos el valor del status del pedido
			$status="";
			
			// Validacion de firma y parámetros
			if ($firma_local === $firma_remota
					&& checkImporte($total)					
					&& checkPedidoNum($pedido)
					&& checkFuc($codigo)
					&& checkMoneda($moneda)
					&& checkRespuesta($respuesta)
					&& $tipoTrans == $tipoTransOrig
					&& $codigo == $codigoOrig
					&& intval(strval($terminalOrig)) == intval(strval($terminal))
				) {
					// Respuesta cumple las validaciones
					$respuesta = intval($respuesta);
					$this->escribirLog($idLog." - Código de respuesta: ".$respuesta,$logActivo);
					if ($respuesta < 101){
							//Mage::log('Redsys: Pago aceptado');
							$this->escribirLog($idLog." - Pago aceptado.",$logActivo);
							//Correo electrónico
							$correo = Mage::getStoreConfig('payment/redsys/correo',Mage::app()->getStore());
							$mensaje = Mage::getStoreConfig('payment/redsys/mensaje',Mage::app()->getStore());
							$nombreComercio = Mage::getStoreConfig('payment/redsys/nombre',Mage::app()->getStore());
							//Datos del cliente
							$customer = Mage::getSingleton('customer/session')->getCustomer();
							if($correo!="0"){
							    $email_to = $customer->getEmail();
								$email_subject = "-MAGENTO- Pedido realizado";
								if(mail($email_to, $email_subject, $mensaje,"From:".$nombreComercio)){
									echo "Correo enviado";
								} else {
									echo "No se puedo enviar el correo";
								}
							}
							//Fin de correo
							
							//Id pedido
							$ord    = $pedido;
							//$orde    = intval(substr($ord, -8));
							$orde    = intval(substr($ord, 0, 11));
							$order = Mage::getModel('sales/order')->loadByIncrementId($orde);

							$transaction_amount = number_format($order->getBaseGrandTotal(),2,'', '');
							$amountOrig = (float)$transaction_amount;
							
							if ($amountOrig != $total) {
								$this->escribirLog($idLog." -- "."El importe total($amountOrig) no coincide con el recibido desde el servidor($total)",$logActivo);
								//Mage::log('Redsys: Diferente importe');
								// Diferente importe
								$state = 'new';
								$status = 'canceled';
								$comment = 'Redsys ha actualizado el estado del pedido con el valor "'.$status.'"';
								$isCustomerNotified = true;
								$order->setState($state, $status, $comment, $isCustomerNotified);
								$order->registerCancellation("")->save();
								$order->save();
								//$this->_redirect('checkout/onepage/failure');
								$this->escribirLog($idLog." -- "."El pedido con ID de carrito " . $orde . " es inválido.",$logActivo);
							}

							try {
							
								if(!$order->canInvoice()) {
									$order->addStatusHistoryComment('Redsys, imposible generar Factura.', false);
									$order->save();
								}
								//START Handle Invoice
								$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
								$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
								$invoice->register();
								$invoice->getOrder()->setCustomerNoteNotify(true);
								$invoice->getOrder()->setIsInProcess(true);
								$order->addStatusHistoryComment('Redsys ha generado la Factura del pedido', false);
								$transactionSave = Mage::getModel('core/resource_transaction')
									->addObject($invoice)
									->addObject($invoice->getOrder());
								$transactionSave->save();
								//END Handle Invoice
								
								//START Handle Shipment
								//$shipment = $order->prepareShipment();
								//$shipment->register();
								//$order->setIsInProcess(true);
								//$order->addStatusHistoryComment('Redsys ENVIO.', false);
								//$transactionSave = Mage::getModel('core/resource_transaction')
								//	->addObject($shipment)
								//	->addObject($shipment->getOrder())
								//	->save();
								//END Handle Shipment
								
								//Email al cliente
								$order->sendNewOrderEmail();
								echo "Pedido: $ord se ha enviado correctamente\n";
							
								//Se actualiza el pedido
								$state = 'new';
								$status = 'processing';
								$comment = 'Redsys ha actualizado el estado del pedido con el valor "'.$status.'"';
								$isCustomerNotified = true;
								$order->setState($state, $status, $comment, $isCustomerNotified);
								$order->save();
								$this->escribirLog($idLog." -- "."El pedido con ID de carrito " . $orderId . " es válido y se ha registrado correctamente.",$logActivo);
								$this->_redirect('checkout/onepage/success');
								
							} catch (Exception $e) {
								$this->escribirLog($idLog." -- "."Excepción: $e ",$logActivo);
								$order->addStatusHistoryComment('Redsys: Exception message: '.$e->getMessage(), false);
								$order->save();
							}
					}
					else {
							$this->escribirLog($idLog." - Pago no aceptado",$logActivo);
							$ord    = $pedido;
							$orde    = intval(substr($ord, 0, 11));
							$order = Mage::getModel('sales/order')->loadByIncrementId($orde);
							$state = 'new';
							$status = 'canceled';
							$comment = 'Redsys ha actualizado el estado del pedido con el valor "'.$status.'"';
							$this->escribirLog($idLog." - Actualizado el estado del pedido con el valor ".$status,$logActivo);
							$isCustomerNotified = true;
							$order->setState($state, $status, $comment, $isCustomerNotified);
							$order->registerCancellation("")->save();
							$order->save();
							//$this->_redirect('checkout/onepage/failure');
					}
				
				}// if (firma_local!=firma_remota)
				else {
					$this->escribirLog($idLog." - Validaciones NO superadas",$logActivo);
					$ord    = $pedido;
					$orde    = intval(substr($ord, 0, 11));
					$order = Mage::getModel('sales/order')->loadByIncrementId($orde);
					$state = 'new';
					$status = 'canceled';
					$comment = 'Redsys ha actualizado el estado del pedido con el valor "'.$status.'"';
					$isCustomerNotified = true;
					$order->setState($state, $status, $comment, $isCustomerNotified);
					$order->registerCancellation("")->save();
					$order->save();
					//$this->_redirect('checkout/onepage/failure');
					}
		
		
        } 
		else if (!empty($_GET)) //URL OK Y KO
        { 
			/** Recoger datos de respuesta **/
			$version     = $_GET["Ds_SignatureVersion"];
			$datos    = $_GET["Ds_MerchantParameters"];
			$firma_remota    = $_GET["Ds_Signature"];
			
			// Se crea Objeto
			$miObj = new RedsysAPI;
			
			/** Se decodifican los datos enviados y se carga el array de datos **/
			$decodec = $miObj->decodeMerchantParameters($datos);

			/** Clave **/
			$kc = Mage::getStoreConfig('payment/redsys/clave256',Mage::app()->getStore());
			
			/** Se calcula la firma **/
			$firma_local = $miObj->createMerchantSignatureNotif($kc,$datos);
			
			/** Extraer datos de la notificación **/
			$total     = $miObj->getParameter('Ds_Amount');
			$pedido    = $miObj->getParameter('Ds_Order');
			$codigo    = $miObj->getParameter('Ds_MerchantCode');
			$terminal  = $miObj->getParameter('Ds_Terminal');
			$moneda    = $miObj->getParameter('Ds_Currency');
			$respuesta = $miObj->getParameter('Ds_Response');
			$fecha	   = $miObj->getParameter('Ds_Date');
			$hora	   = $miObj->getParameter('Ds_Hour');
			$id_trans  = $miObj->getParameter('Ds_AuthorisationCode');
			$tipoTrans = $miObj->getParameter('Ds_TransactionType');

			// Recogemos los datos del comercio
			$codigoOrig = Mage::getStoreConfig('payment/redsys/num',Mage::app()->getStore());	
			$terminalOrig = Mage::getStoreConfig('payment/redsys/terminal',Mage::app()->getStore());
			$monedaOrig =Mage::getStoreConfig('payment/redsys/moneda',Mage::app()->getStore());
			$tipoTransOrig =Mage::getStoreConfig('payment/redsys/trans',Mage::app()->getStore());

			// Obtenemos el código ISO del tipo de moneda
			if($monedaOrig == "0"){
				$monedaOrig = "978";
			} else { 
				$monedaOrig = "840";
			}

			if ($firma_local === $firma_remota
					&& checkImporte($total)					
					&& checkPedidoNum($pedido)
					&& checkFuc($codigo)
					&& checkMoneda($moneda)
					&& checkRespuesta($respuesta)
					&& $tipoTrans == $tipoTransOrig
					&& $codigo == $codigoOrig
					&& intval(strval($terminalOrig)) == intval(strval($terminal))
				) {
					$respuesta = intval($respuesta);
					$orde    = intval(substr($pedido, 0, 11));
					$order = Mage::getModel('sales/order')->loadByIncrementId($orde);
					if ($respuesta < 101){
						$transaction_amount = number_format($order->getBaseGrandTotal(),2,'', '');
						$amountOrig = (float)$transaction_amount;
						if ($amountOrig != $total) {
							$this->_redirect('checkout/onepage/failure');
						} else {
							$this->_redirect('checkout/onepage/success');
						}
					} else {
						if(strval($mantenerPedidoAnteError) == 1) {
							$_order = new Mage_Sales_Model_Order();
							$orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
							$_order->loadByIncrementId($orderId);
							
							$items = $_order->getAllVisibleItems();
							$cart = Mage::getSingleton('checkout/cart');
							foreach ($items as $itemId => $item){
								$this->escribirLog($idLog." - Cargado carrito con ".$item->getName(),$logActivo);
								$cart->addOrderItem($item);
							}
							$cart->save();
						}
						$this->_redirect('checkout/onepage/failure');
					}
				} else {
					$this->_redirect('checkout/onepage/failure');
				}
        } else echo 'No hay respuesta por parte de Redsys!';
	}

    public function indexAction() {

        echo 'Hello MAGENTO!';

    }

	public function escribirLog($texto,$activo) {
		if($activo==1){
			Mage::log('Redsys: '.$texto);
		}
	}

}


?>