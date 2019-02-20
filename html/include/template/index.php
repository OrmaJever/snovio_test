<!DOCTYPE html>
<html>
<head>
	<title>Snovio test</title>
	<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
</head>
<body>
	<table>
		<tr><td>Адрес сайта:</td><td><input type="text" name="url" value="https://snov.io/" placeholder="https://snov.io/"></td></tr>
		<tr><td>Глубина:</td><td><input type="text" name="deep" value="1" placeholder="0"></td></tr>
		<tr><td>Количество адресов:</td><td><input type="text" value="10" name="email_max" placeholder="10"></td></tr>
		<tr><td><button onclick="get_email()">Получить</button></td></tr>
	</table>
	<img src="https://media.giphy.com/media/3oEjI6SIIHBdRxXI40/giphy.gif" id="loading" style="display:none">
	<div id="results">
		
	</div>
	<script>

		function get_email() {
			let url = $('input[name=url]').val();
			let deep = $('input[name=deep]').val();
			let email_max = $('input[name=email_max]').val();

			if( !url.length ) {
				return;
			}
			$('#loading').show();
			$('#results').text('Процесс пошёл, после окончания нужно ещё раз нажать на кнопку и данные прогрузятся уже из базы');
			$.post('/index/parse', {url: url, deep: deep, email_max: email_max}, (data) => {
				$('#loading').hide();
				$('#results').text('');
				if(data.status == 'error') {
					alert(data.data);
				} else {
					data.data.forEach((data) => {
						$('#results').append('-'.repeat(data.i*4) + data.url + '<br>');
						data.emails.forEach((email) => {
							$('#results').append('-'.repeat(data.i*4+4) + email + '<br>');
						});
						$('#results').append('<br>');
					})
				}
			});
		}
	</script>
</body>
</html>