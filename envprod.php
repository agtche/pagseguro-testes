<?php

	$url = "https://ws.pagseguro.uol.com.br/v2/sessions";

	$credenciais = array(
			"email" => "<<insira seu email aqui>>",
			"token" => "<<token pagseguro aqui>>"
	);
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($credenciais));
	$resultado = curl_exec($curl);
	curl_close($curl);
	$session = simplexml_load_string($resultado)->id;
?>
<!DOCTYPE html>
<html>
<meta charset="utf-8">
<head>
	<title>Testes Integração com Checkout Transparente PagSeguro</title>
	<script type="text/javascript"></script>
</head>
<body>

	<h1>Testes Rápidos Integração com Checkout Transparente PagSeguro</h1><hr>
	<fieldset>
	<legend>Gerar o SenderHash</legend>
	<div> 
	<button id="generateSenderHash">Gerar SenderHash</button><input type="text" id="senderHash" name="senderHash" size="65">
	</div>
	</fieldset>
	<br>

	<fieldset>
	<legend>Chamadas para Cartão de Crédito</legend>
	<div>
		Número do cartão: <input type="text" id="creditCardNumber" class="creditcard" name="creditCardNumber">
	
		Bandeira: <input type="text" id="creditCardBrand" class="creditcard" name="creditCardBrand" disabled>

		Validade Mês (mm):  <input type="text" id="creditCardExpMonth" class="creditcard" name="creditCardExpMonth" size="2"> &nbsp;

		Ano (yyyy) <input type="text" id="creditCardExpYear" class="creditcard" name="creditCardExpYear" size="4">

		CVV: <input type="text" id="creditCardCvv" class="creditcard" name="creditCardCvv">
	<button id="generateCreditCardToken">Gerar Token</button>
	<input type="text" id="creditCardToken" name="creditCardToken" disabled>
	</fieldset>
	<br>
	<fieldset>
	<legend>Parcelamento</legend>
		Valor do Checkout: <input type="text" id="checkoutValue" name="checkoutValue">
	<button id="installmentCheck">Ver Parcelamento</button>
	</p>
	<p>
	<select id="InstallmentCombo">
	</select>
	</p>
	</fieldset>

</body>
<!-- Incluíndo o arquivo JS do PagSeguro e o JQuery-->
<script type="text/javascript" src="https://stc.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<!-- Funcionalidade do JS -->
<script type="text/javascript">

	//Setando o session ID
	PagSeguroDirectPayment.setSessionId('<?php echo $session; ?>');
	console.log('<?php echo $session; ?>');

	//Get CreditCard Brand and check if is Internationl
	$("#creditCardNumber").keyup(function(){
		if ($("#creditCardNumber").val().length >= 6) {
			PagSeguroDirectPayment.getBrand({
				cardBin: $("#creditCardNumber").val().substring(0,6),
				success: function(response) { 
						console.log(response);
						$("#creditCardBrand").val(response['brand']['name']);
						$("#creditCardCvv").attr('size', response['brand']['cvvSize']);
				},
				error: function(response) {
					console.log(response);
				}
			})
		};
	})

	function printError(error){
		$.each(error['errors'], (function(key, value){
			console.log("Foi retornado o código " + key + " com a mensagem: " + value);
		}));
	}

	function getPaymentMethods(valor){
		PagSeguroDirectPayment.getPaymentMethods({
			amount: valor,
			success: function(response) {
				//console.log(JSON.stringify(response));
				console.log(response);
			},
			error: function(response) {
				console.log(JSON.stringify(response));
			}
		})
	}	

	//Generates the creditCardToken
	$("#generateCreditCardToken").click(function(){
		var param = {
			cardNumber: $("#creditCardNumber").val(),
			cvv: $("#creditCardCvv").val(),
			expirationMonth: $("#creditCardExpMonth").val(),
			expirationYear: $("#creditCardExpYear").val(),
			success: function(response) {
				console.log(response);
				$("#creditCardToken").val(response['card']['token']);
			},
			error: function(response) {
				console.log(response);
				printError(response);
			}
		}
			//parâmetro opcional para qualquer chamada
			if($("#creditCardBrand").val() != '') {
				param.brand = $("#creditCardBrand").val();
			}
			PagSeguroDirectPayment.createCardToken(param);
	})

	//Check installment
	$("#installmentCheck").click(function(){
		if($("#creditCardBrand").val() != '') {
			getInstallments();
		} else {
			alert("Uma bandeira deve estar selecionada");
		}
	})

	function getInstallments(){
		var brand = $("#creditCardBrand").val();
		PagSeguroDirectPayment.getInstallments({
			amount: $("#checkoutValue").val().replace(",", "."),
			brand: brand,
			maxInstallmentNoInterest: 2, //calculo de parcelas sem juros
			success: function(response) {
				var installments = response['installments'][brand];
				buildInstallmentSelect(installments);
			},
			error: function(response) {
				console.log(response);
			}
		})
	}

	function buildInstallmentSelect(installments){
		$.each(installments, (function(key, value){
			$("#InstallmentCombo").append("<option value = "+ value['quantity'] +">" + value['quantity'] + "x de " + value['installmentAmount'].toFixed(2) + " - Total de " + value['totalAmount'].toFixed(2)+"</option>");
		}))
	}

	//Get SenderHash
	$("#generateSenderHash").click(function(){
		$("#senderHash").val(PagSeguroDirectPayment.getSenderHash());
		
	})
</script>

</html>