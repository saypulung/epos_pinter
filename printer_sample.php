<html>	
	<head>
		<title>Printer Test</title>
		<link rel="stylesheet" href="printer/css/bootstrap.min.css"/>
		<link rel="stylesheet" href="printer/css/font-awesome.min.css"/>
		<script src="printer/js/additional/jquery-1.11.3.min.js"></script>
		<script src="printer/js/additional/bootstrap.min.js"/></script>
		<script src="printer/js/dependencies/rsvp-3.1.0.min.js"/></script>
		<script src="/epos/epos-2.9.0.js"/></script>
		<script src="/epos/xdate.js"/></script>
		<style>
			.form-group{
				padding-top:10px !important;
				padding-bottom:10px  !important;
			}
		</style>
	</head>
	<body>
		<div class="container">
			<div class="row">
				<div class="col-md-4">
					<h3>Configuration</h3>
					<div class="form-group">
						<div class="col-md-8">
							<input type="text" class="form-control" id="ipaddr" placeholder="IP Address"/>
						</div>
					</div>
					<div class="form-group">
						<div class="col-md-12">
							<input type="text" class="form-control" id="port" placeholder="Port"/>
						</div>
					</div>
					<div class="form-group">
						<div class="col-md-12">
							<br><button id="Set" class="btn btn-primary btn-block">Set</button>
						</div>
					</div>
				</div>
				<div class="col-md-8">
					<h3>Data</h3>
					<div class="row">
						<div class="col-md-12">
							<div class="form-inline">
								<div class="form-group">
									<input class="form-control" id="Id" type="text" placeholder="ID" style="width:50px"/>
								</div>
								<div class="form-group">
									<input class="form-control" id="Name" type="text" placeholder="Name"/>
								</div>
								<div class="form-group">
									<input class="form-control" id="Qty" type="text" placeholder="Qty" style="width:50px"/>
								</div>
								<div class="form-group">
									<button id="addData" class="btn btn-primary">Add</button>
								</div>
							</div>
						</div>
						<div class="col-md-12">
							<table class="table table-striped table-bordered">
								<thead>
									<tr>
										<th>No</th>
										<th>ID</th>
										<th>Name</th>
										<th>Qty</th>
									</tr>
								</thead>
								<tbody id="datapool">
									
								</tbody>
							</table>
						</div>
						<div class="col-md-12">
							<button id="printData" class="btn btn-block btn-info">Print</button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script>
			var dataPrint = [];
			var ipAddr = "";
			var port = 0;
			var printerDev = null;
			var eposDev = new epson.ePOSDevice();
			var createCookie = function(name, value, days) {
				var expires;
				if (days) {
					var date = new Date();
					date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
					expires = "; expires=" + date.toGMTString();
				}
				else {
					expires = "";
				}
				document.cookie = name + "=" + value + expires + "; path=/";
			}

			function getCookie(c_name) {
				if (document.cookie.length > 0) {
					c_start = document.cookie.indexOf(c_name + "=");
					if (c_start != -1) {
						c_start = c_start + c_name.length + 1;
						c_end = document.cookie.indexOf(";", c_start);
						if (c_end == -1) {
							c_end = document.cookie.length;
						}
						return unescape(document.cookie.substring(c_start, c_end));
					}
				}
				return "";
			}
			
			$(function(){
				var ipaddrEl = $('#ipaddr');
				var portEl = $('#port');
				var Id = $('#Id');
				var Name = $("#Name");
				var Qty = $("#Qty");
				var DataPool = $('#datapool');
				var appendTable = ()=>{
					var no = 0;
					var elements = "";
					dataPrint.map((item,key)=>{
						no++;
						elements+="<tr><td>"+no+"</td><td>"+item.id+"</td><td>"+item.name+"</td><td>"+item.qty+"</td></tr>";
					});
					DataPool.html(elements);
					
				};
				clearInput = ()=>{
					Id.val('');
					Name.val('');
					Qty.val('');
				};
				function printToHardware(){
					if(dataPrint.length==0){
						alert('Please, create data first');
						return;
					}
					reloadSetting();
					var ip = ipaddrEl.val();
					var port = portEl.val();
					if(ip=='' && port==''){
						alert('Please set printer first');
						return;
					}
					eposDev.connect(ip,port,resultConnecting);
				}
				function resultConnecting(data){
					if(data == 'OK'){
						eposDev.createDevice('local_printer', eposDev.DEVICE_TYPE_PRINTER, {'crypto' : false, 'buffer' : false}, resultCreateDevice);
					}
					if(data == 'SSL_CONNECT_OK'){
						eposDev.createDevice('local_printer', eposDev.DEVICE_TYPE_PRINTER, {'crypto' : true, 'buffer' : false}, resultCreateDevice);
					}
					else{
						alert('Printer connection error, message = '+data);
					}
				}
				function resultCreateDevice(devobj, retcode){
					if(retcode=='OK'){
						printer = devobj;
						var dataWillPrinter = buildPrintData();
						dataWillPrinter.map((item,key)=>{
							printer.addText(item);
						});
						printer.addCut(printer.CUT_FEED);
						printer.send();
					}else{
						alert('Printer device error '+retcode);
						return;
					}
					
				}
				
				var reloadSetting = ()=>{
					var c = getCookie('settings');
					var x = JSON.parse(c);
					if(typeof x !='undefined'){
						if(typeof x.ipaddr !='undefined'){
							ipaddrEl.val(x.ipaddr);
						}
						if(typeof x.port !='undefined'){
							portEl.val(x.port);
						}
					}
				};
				var buildPrintData = ()=>{
					var printdata = [];
					printdata.push("---------------------------------------\n");
					printdata.push("Transaction Sample\n");
					printdata.push("Tanggal : "+new XDate().toString('dd MMM yyy')+"\n");
					printdata.push("---------------------------------------\n");
					printdata.push("No\tID\tName\tQty\t\n");
					var no  = 0;
					dataPrint.map((item,key)=>{
						no++;
						printdata.push(""+no+"\t"+item.id+"\t"+item.name+"\t"+item.qty+"\n");
					});
					printdata.push("---------------------------------------\n");
					console.log(printdata);
					return printdata;
				};
				$('#addData').click(()=>{
					dataPrint.push({id:Id.val(),name:Name.val(),qty:Qty.val()});
					appendTable();
					clearInput();
				});
				$("#Set").click(()=>{
					var Json = JSON.stringify({ipaddr:ipaddrEl.val(),port:portEl.val()});
					createCookie('settings',Json);
				});
				$('#printData').click(()=>{
					printToHardware();
				});
				reloadSetting();
			});
		</script>
	</body>
</html>