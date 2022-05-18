<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Login route
Route::post('/login', function (Request $request) {
	$email = $request->email;
	$password = $request->password;

  $exist = DB::connection('sqlsrv')->select('select * from cms_users where email = ?', [$email]);

  $userdata = array(
    'email'     => $email,
    'password'  => $password
  );

  //Si existe el usuario
  $dataArray = [
    'error' => 1,
    'id' => $exist[0]->id,
    'message' => '¡Email y/o password incorrectos!'
  ];
	if($exist):
		//If exists and the paswords match
    if(Hash::check($password, $exist[0]->password)):
      
			$dataArray = [
				'error' => 0,
        'nombre' => $exist[0]->name,
        'id' => $exist[0]->id
        
      ];
      
      $sql = "select au_permissions_id
              from au_users_permissions
              where cms_users_id=".$exist[0]->id;
      $permissions = DB::connection('sqlsrv')->select($sql);

      $per = "";
      if(!empty($permissions))
      foreach($permissions as $p):
        $per .= $p->au_permissions_id.",";
      endforeach;
      $dataArray["permissions"] = substr($per,0,-1);
      $dataArray["vendedor"] = $exist[0]->vendedor;
		else:
			$dataArray = [
				'error' => 1,
		    	'id' => $exist[0]->id,
		    	'message' => '¡Email y/o password incorrectos!'
			];
		endif;
	endif;
	
	return $dataArray;
	
});
//Change Pass route
Route::post('/change-pass', function (Request $request) {
	$user = $request->user;
	$password = $request->pass;
	$currentpass = $request->currentpass;

  if(empty($password) || empty($currentpass)) return;

  $oldP = Hash::make($currentpass);

  $newP = Hash::make($password);

  // Update the order status
  $dataArray = [
    'password'=>$newP
  ];

  DB::table('cms_users')
          ->where('id', $user)
          ->update($dataArray);
	
	return 1;
	
});

//We get all the vendedores
Route::post('/get-vendedores', function (Request $request) {
  $sql = "select 
            SlpCode value,
            SlpName label
          from  
            OSLP
          where SlpCode != -1";
	$vendedor = DB::connection('sqlsrv2')->select($sql);
	return $vendedor;
});
//We get all the configurarion
Route::post('/get-config', function (Request $request) {
  $sql = "select 
            *
          from 
            au_config";
	$config = DB::connection('sqlsrv')->select($sql);
	return $config;
});
//We get tipos de pago
Route::post('/get-tipo-pago', function (Request $request) {
  $sql = "select 
            id value, 
            tipo label
          from 
            au_tipo_pago";
	$config = DB::connection('sqlsrv')->select($sql);
	return $config;
});
//We get if we have updated prices
Route::post('/get-prices-flag', function (Request $request) {
  $sql = "select 
            TOP 1 1 ban
          from 
            au_precios
          where 
            DAY(getdate())=DAY(Fecha)
          and 
            MONTH(getdate())=MONTH(Fecha)
          and 
            YEAR(getdate())=YEAR(Fecha)";
	$flag = DB::connection('sqlsrv')->select($sql);
	return $flag;
});
//We get all of the news
Route::post('/get-clients', function (Request $request) {
  $sql = "select
            CardName+' - '+ CardCode label,
            a.CardCode value,
            address,
            MailAddres,
            a.U_NIT,
            NumSAP = b.GroupNum,
            CantDias = (b.ExtraMonth * 30) + b.ExtraDays 
          from
            OCRD a inner join
            OCTG b on a.GroupNum = b.GroupNum
          WHERE
            CARDTYPE='C'";
  $clients = DB::connection('sqlsrv2')->select($sql);
  
  foreach($clients as &$c):
    $sql = "Select  
              Street value, 
              Street label
            from    
              CRD1
            where   
              AdresType='S'
            and   
              CardCode='".$c->value."'";
    $c->addresses = DB::connection('sqlsrv2')->select($sql);

    $sql = "select  
              id value,
              tipo label,
              sap,
              dias
            from    
              au_tipo_pago
            where   
              dias <= ".$c->CantDias;
    $c->tipo_pago = DB::connection('sqlsrv')->select($sql);

    foreach($c->tipo_pago as $t):
      if($t->dias == $c->CantDias):
        $c->NumSAP = $t->value;
        $c->NumSAPLabel = $t->label;
      endif;
    endforeach;

  endforeach;
	return $clients;
});

//We get all of the products
Route::post('/get-products', function (Request $request) {
  $plant = $request->plant;
  $fecha = $request->fechaEntrega;
  // $fecha = explode("",$fecha)[0];  

	$sql = "select  nombre label,
                  codigoSAP value,
                  Color color,
                  ProductoExclusivo exclusivo, 
                  p.IDP,
                  p.Margen,
                  (select TOP 1 CAST(precio AS DECIMAL(18,2)) precio  from au_precios r where p.id=r.au_producto_id and r.au_planta_id=".$plant." and Fecha = '".$fecha."' order by created_at desc, updated_at desc) precio,
                  (select top 1 0 from au_precios r where p.id=r.au_producto_id and r.au_planta_id=".$plant." and Fecha = '".$fecha."') no_oficial
            from    au_producto p";
	$products = DB::connection('sqlsrv')->select($sql);
	return $products;
});

//We get the plantas
Route::post('/get-plants', function (Request $request) {
  $sql = "select 
            nombre label,
            id value
          from 
            au_planta";
	$plantas = DB::connection('sqlsrv')->select($sql);
	return $plantas;
});

//We get all of the fletes
Route::post('/get-fletes', function (Request $request) {
  $sql = "select 
            id value,
            NumeroUnidad label,
            id,
            codigoSAP
          from 
            au_flete f";
    $fletes = DB::connection('sqlsrv')->select($sql);

    foreach($fletes as &$f):
        $sql = "select 
                  * 
                from 
                  au_compartimientos 
                where 
                  au_flete_id=".$f->id."
                order by Orden asc";
        $f->compartimientos = DB::connection('sqlsrv')->select($sql);
    endforeach;

	return $fletes;
});

//We get all othe orders
Route::post('/get-order', function (Request $request) {
  $id = $request->id;
  $sql = "select  o.id,
            o.CodigoCliente,
            RTRIM(LTRIM(tp.tipo)) tipo_pago,
            RTRIM(LTRIM(tp.tipo)) NumSAPLabel,
            tp.id id_tp,
            o.Direccion,
            o.FleteAplicado,
            o.FleteXGalon,
            f.CodigoSAP flete,
            f.Placa placa,
            o.Comentarios,
            ou.status,
            CONVERT(VARCHAR(8),
            o.FechaCarga,3) FechaCarga,
            o.FechaCarga fecha_carga,
            CONVERT(varchar(15),
            o.HoraCarga,108) HoraCarga, 
            CONVERT(VARCHAR(8),o.created_at,3) creado,
            o.au_planta_id planta,
            o.au_order_status_id sid,
            o.HorarioAsignado,
            o.CreditoValidado, 
            o.Chevron,
            o.Fletero,
            o.Invoice,
            o.Programado,
            o.created_at,
            CodigoProveedorSAP, 
            f.CodigoSAP codigoTransporte,
            p.nombre NombrePlanta,
            f.NumeroUnidad,
            o.idFlete,
            o.generar_entrada_mercancia,
            o.generar_factura,
            o.vendedor vendedor_id,
            o.contra_boleta
          from au_order o
          left join au_flete f on o.idFlete=f.id
          INNER JOIN au_order_status ou
          on o.au_order_status_id=ou.id
          INNER JOIN au_tipo_pago tp
          on o.TipoPago=tp.id
          INNER JOIN au_planta p
          ON o.au_planta_id=p.id
          WHERE o.id=".$id;
    $orders = DB::connection('sqlsrv')->select($sql);

    $sql = "select  id value,
                    tipo label,
                    sap,
                    dias
            from    au_tipo_pago";
    $tipos_pago = DB::connection('sqlsrv')->select($sql);

    if(!empty($orders))
    foreach($orders as &$o):
      $cod = trim($o->CodigoCliente);
      $o->CodigoClienteSap = $cod;
      $sql = "select
                CardName+' - '+ CardCode label,
                QryGroup1 star,
                NumSAP = b.GroupNum,
                CantDias = (b.ExtraMonth * 30) + b.ExtraDays 
              from
                OCRD a inner join
                OCTG b on a.GroupNum = b.GroupNum
              WHERE
                CARDTYPE='C'
              and    
                CardCode='".$o->CodigoCliente."'";
      $res = DB::connection('sqlsrv2')->select($sql);

      if(!empty($res)):
        $o->CodigoCliente = $res[0]->label;
        $o->star = $res[0]->star;
        $o->NumSAP = $res[0]->NumSAP;

        $tpago = [];

        if(!empty($tipos_pago))
        foreach($tipos_pago as $tp):
          if($tp->dias <= $res[0]->CantDias):
            $tpago[] = $tp;
          endif;
        endforeach;

        $o->tipos_pago = $tpago;

      endif;

      $sql = "SELECT od.cantidad,
                    od.Compartimiento,
                    p.Nombre,
                    p.Color,
                    Cast(od.idp AS DECIMAL(18, 2))    IDP,
                    Cast(od.costo AS DECIMAL(18, 2))  Costo,
                    Cast(od.precio AS DECIMAL(18, 2)) Precio
              FROM   au_order_detail od
                    INNER JOIN au_producto p
                            ON od.producto = p.codigosap
              WHERE  od.au_order_id = ".$o->id."
              ORDER  BY compartimiento ASC";
      $o->Compartimientos = DB::connection('sqlsrv')->select($sql);

      $detail = "";
      $costo = 0;
      $venta = 0;
      foreach($o->Compartimientos as $c):
        $detail .= "<strong>Comp: ".$c->Compartimiento." - ".$c->Nombre . ":</strong> ".$c->cantidad."<br>";
        $costo += $c->Costo*$c->cantidad;
        $venta += $c->Precio*$c->cantidad;
      endforeach;

      $o->costo = $costo;
      $o->venta = $venta;
      $o->detalle= $detail;
      
      $sql = "select 
                  c.created_at,
                  c.updated_at,
                  u.name,
                  c.Comentario comentario
              from 
                au_order_comments c
                INNER JOIN cms_users u
                ON c.cms_users_id=u.id
              where c.au_order_id=".$o->id."
              order by c.created_at desc";
      $o->comentarios = DB::connection('sqlsrv')->select($sql);

      $sql = 'select	
                  CONVERT(VARCHAR(8),l.created_at,3) fecha,
                  convert(char(5), l.created_at, 108) hora,
                  DATEADD(ms, -DATEPART(ms, l.created_at), l.created_at) created_at,
                  u.name,
                  t.type,
                  l.Texto,
                  l.id
              from  
                au_log l
                INNER JOIN cms_users u
                  ON l.cms_users_id=u.id
                INNER JOIN au_log_type t
                  ON l.au_log_type_id = t.id
              where "Order" = '.$o->id."
              order by l.created_at desc";
      $o->log = DB::connection('sqlsrv')->select($sql);

      $sql = "select 
                TOP 1 ShortName,
                RefDate fecha,
                Ref1 documento,
                BalDueDeb debit,
                BalDueCred credit
              from 
                JDT1
              where 
                ShortName = '".$cod."'
              AND 
                BalDueDeb + BalDueCred > 0";
      $o->credit = DB::connection('sqlsrv2')->select($sql)[0];
      $sql = "select
                a.CardCode,
                a.CardName,
                a.CreditLine,
                a.Balance,
                b.TransType,
                b.Ref1,
                CONVERT(VARCHAR(20),b.RefDate,103) Fecha_Emision,
                CONVERT(VARCHAR(20),b.DueDate,103) Fecha_Vencimiento,
                Monto_Original = b.Debit - Credit,
                Monto_Vencido = b.BalDueDeb + b.BalDueCred 
              from
                OCRD a inner join
                JDT1 b on a.CardCode = b.ShortName
              where
                CardCode = '".$cod."'
                and b.BalDueCred + b.BalDueDeb > 0";
      $o->credit_detail = DB::connection('sqlsrv2')->select($sql);

      // Search if the code exists
      $sql = "select 
                1
              from 
                OCRD
              where 
                frozenFor='N'
              and 
                CardCode='".$o->CodigoProveedorSAP."'";
      $o->CodigoProveedorSAPExist = count(DB::connection('sqlsrv2')->select($sql));

      $sql = "select 
                1
              from 
                OCRD
              where 
                frozenFor='N'
              and 
                CardCode='".$o->codigoTransporte."'";
      $o->codigoTransporteExist = count(DB::connection('sqlsrv2')->select($sql));
      
      $sql = "select 
                1
              from 
                OCRD
              where 
                frozenFor='N'
              and 
                CardCode='".$o->CodigoClienteSap."'";
      $o->CodigoClienteSapExist = count(DB::connection('sqlsrv2')->select($sql));

      if(!empty($o->vendedor_id)):
        // Get vendedor
        $sql = "select 
              SlpCode value,
              SlpName label
            from  
              OSLP
            where SlpCode = ".$o->vendedor_id;
        $vendedor = DB::connection('sqlsrv2')->select($sql);
        
        if(!empty($vendedor)):
          $o->vendedor = $vendedor[0]->label;
        endif;
      else:
        $o->vendedor = "";
      endif;

    endforeach;

	return $orders;
});
Route::post('/get-orders', function (Request $request) {
  $sql = "select  o.id,
            o.CodigoCliente,
            RTRIM(LTRIM(tp.tipo)) tipo_pago,
            RTRIM(LTRIM(tp.tipo)) NumSAPLabel,
            tp.id id_tp,
            o.Direccion,
            o.FleteAplicado,
            o.FleteXGalon,
            f.CodigoSAP flete,
            f.Placa placa,
            o.Comentarios,
            ou.status,
            CONVERT(VARCHAR(8),
            o.FechaCarga,3) FechaCarga,
            o.FechaCarga fecha_carga,
            CONVERT(varchar(15),
            o.HoraCarga,108) HoraCarga, 
            CONVERT(VARCHAR(8),o.created_at,3) creado,
            o.au_planta_id planta,
            o.au_order_status_id sid,
            o.HorarioAsignado,
            o.CreditoValidado, 
            o.Chevron,
            o.Fletero,
            o.Invoice,
            o.Programado,
            o.created_at,
            CodigoProveedorSAP, 
            f.CodigoSAP codigoTransporte,
            p.nombre NombrePlanta,
            f.NumeroUnidad,
            o.idFlete,
            o.nombre_vendedor,
            o.vendedor,
            o.contra_boleta
          from au_order o
          left join au_flete f on o.idFlete=f.id
          INNER JOIN au_order_status ou
          on o.au_order_status_id=ou.id
          INNER JOIN au_tipo_pago tp
          on o.TipoPago=tp.id
          INNER JOIN au_planta p
          ON o.au_planta_id=p.id
          WHERE 
		  --DATEPART(m, o.FechaCarga) >= DATEPART(m, DATEADD(m, -1, getdate()))
          --AND DATEPART(yyyy, o.FechaCarga) = DATEPART(yyyy, DATEADD(m, -1, getdate()))
 		  (year(o.FechaCarga) * 100) + month(o.FechaCarga) >= (year(DATEADD(m, -1, getdate())) * 100) + month(DATEADD(m, -1, getdate()))
          order by o.created_at desc";
    $orders = DB::connection('sqlsrv')->select($sql);

    if(!empty($orders))
    foreach($orders as &$o):
      $cod = trim($o->CodigoCliente);
      $o->CodigoClienteSap = $cod;
      $sql = "select
                CardName+' - '+ CardCode label,
                QryGroup1 star,
                NumSAP = b.GroupNum,
                CantDias = (b.ExtraMonth * 30) + b.ExtraDays 
              from
                OCRD a inner join
                OCTG b on a.GroupNum = b.GroupNum
              WHERE
                CARDTYPE='C'
              and    
                CardCode='".$o->CodigoCliente."'";
      $res = DB::connection('sqlsrv2')->select($sql);

      if(!empty($res)):
        $o->CodigoCliente = $res[0]->label;
        $o->star = $res[0]->star;
        $o->NumSAP = $res[0]->NumSAP;
      endif;
    endforeach;


    usort($orders,'cmp');

    return $orders;

    $sql = "select  id value,
                    tipo label,
                    sap,
                    dias
            from    au_tipo_pago";
    $tipos_pago = DB::connection('sqlsrv')->select($sql);

    if(!empty($orders))
    foreach($orders as &$o):
      $cod = trim($o->CodigoCliente);
      $o->CodigoClienteSap = $cod;
      $sql = "select
                CardName+' - '+ CardCode label,
                QryGroup1 star,
                NumSAP = b.GroupNum,
                CantDias = (b.ExtraMonth * 30) + b.ExtraDays 
              from
                OCRD a inner join
                OCTG b on a.GroupNum = b.GroupNum
              WHERE
                CARDTYPE='C'
              and    
                CardCode='".$o->CodigoCliente."'";
      $res = DB::connection('sqlsrv2')->select($sql);

      if(!empty($res)):
        $o->CodigoCliente = $res[0]->label;
        $o->star = $res[0]->star;
        $o->NumSAP = $res[0]->NumSAP;

        // $sql = "select  id value,
        //                 tipo label,
        //                 sap
        //         from    au_tipo_pago
        //         where   dias <= ".$res[0]->CantDias;
        // $o->tipos_pago = DB::connection('sqlsrv')->select($sql);


        $tpago = [];

        if(!empty($tipos_pago))
        foreach($tipos_pago as $tp):
          if($tp->dias <= $res[0]->CantDias):
            $tpago[] = $tp;
          endif;
        endforeach;

        $o->tipos_pago = $tpago;

        // foreach($o->tipos_pago as $t):
        //   if($t->value == $o->id_tp):
        //     $o->NumSAP = $t->value;
        //     $o->NumSAPLabel = $t->label;
        //   endif;
        // endforeach;
      endif;

      $sql = "SELECT od.cantidad,
                    od.Compartimiento,
                    p.Nombre,
                    p.Color,
                    Cast(od.idp AS DECIMAL(18, 2))    IDP,
                    Cast(od.costo AS DECIMAL(18, 2))  Costo,
                    Cast(od.precio AS DECIMAL(18, 2)) Precio
              FROM   au_order_detail od
                    INNER JOIN au_producto p
                            ON od.producto = p.codigosap
              WHERE  od.au_order_id = ".$o->id."
              ORDER  BY compartimiento ASC";
      $o->Compartimientos = DB::connection('sqlsrv')->select($sql);

      $detail = "";
      $costo = 0;
      $venta = 0;
      foreach($o->Compartimientos as $c):
        $detail .= "<strong>Comp: ".$c->Compartimiento." - ".$c->Nombre . ":</strong> ".$c->cantidad."<br>";
        $costo += $c->Costo*$c->cantidad;
        $venta += $c->Precio*$c->cantidad;
      endforeach;

      $o->costo = $costo;
      $o->venta = $venta;
      $o->detalle= $detail;
      
      $sql = "select 
                  c.created_at,
                  c.updated_at,
                  u.name,
                  c.Comentario comentario
              from 
                au_order_comments c
                INNER JOIN cms_users u
                ON c.cms_users_id=u.id
              where c.au_order_id=".$o->id."
              order by c.created_at desc";
      $o->comentarios = DB::connection('sqlsrv')->select($sql);

      $sql = 'select	
                  CONVERT(VARCHAR(8),l.created_at,3) fecha,
                  convert(char(5), l.created_at, 108) hora,
                  u.name,
                  t.type,
                  l.Texto,
                  l.id
              from  
                au_log l
                INNER JOIN cms_users u
                  ON l.cms_users_id=u.id
                INNER JOIN au_log_type t
                  ON l.au_log_type_id = t.id
              where "Order" = '.$o->id;
      $o->log = DB::connection('sqlsrv')->select($sql);

      $sql = "select 
                TOP 1 ShortName,
                RefDate fecha,
                Ref1 documento,
                BalDueDeb debit,
                BalDueCred credit
              from 
                JDT1
              where 
                ShortName = '".$cod."'
              AND 
                BalDueDeb + BalDueCred > 0";
      $o->credit = DB::connection('sqlsrv2')->select($sql)[0];
      $sql = "select
                a.CardCode,
                a.CardName,
                a.CreditLine,
                a.Balance,
                b.TransType,
                b.Ref1,
                CONVERT(VARCHAR(20),b.RefDate,103) Fecha_Emision,
                CONVERT(VARCHAR(20),b.DueDate,103) Fecha_Vencimiento,
                Monto_Original = b.Debit - Credit,
                Monto_Vencido = b.BalDueDeb + b.BalDueCred 
              from
                OCRD a inner join
                JDT1 b on a.CardCode = b.ShortName
              where
                CardCode = '".$cod."'
                and b.BalDueCred + b.BalDueDeb > 0";
      $o->credit_detail = DB::connection('sqlsrv2')->select($sql);

      // Search if the code exists
      $sql = "select 
                1
              from 
                OCRD
              where 
                frozenFor='N'
              and 
                CardCode='".$o->CodigoProveedorSAP."'";
      $o->CodigoProveedorSAPExist = count(DB::connection('sqlsrv2')->select($sql));

      $sql = "select 
                1
              from 
                OCRD
              where 
                frozenFor='N'
              and 
                CardCode='".$o->codigoTransporte."'";
      $o->codigoTransporteExist = count(DB::connection('sqlsrv2')->select($sql));
      
      $sql = "select 
                1
              from 
                OCRD
              where 
                frozenFor='N'
              and 
                CardCode='".$o->CodigoClienteSap."'";
      $o->CodigoClienteSapExist = count(DB::connection('sqlsrv2')->select($sql));
    endforeach;

    usort($orders,'cmp');

	return $orders;
});

Route::post('/get-orders-procesadas', function (Request $request) {
  $sql = "select  o.id,
            o.CodigoCliente,
            RTRIM(LTRIM(tp.tipo)) tipo_pago,
            RTRIM(LTRIM(tp.tipo)) NumSAPLabel,
            tp.id id_tp,
            o.Direccion,
            o.FleteAplicado,
            o.FleteXGalon,
            f.CodigoSAP flete,
            f.Placa placa,
            o.Comentarios,
            ou.status,
            CONVERT(VARCHAR(8),
            o.FechaCarga,3) FechaCarga,
            o.FechaCarga fecha_carga,
            CONVERT(varchar(15),
            o.HoraCarga,108) HoraCarga, 
            CONVERT(VARCHAR(8),o.created_at,3) creado,
            o.au_planta_id planta,
            o.au_order_status_id sid,
            o.HorarioAsignado,
            o.CreditoValidado, 
            o.Chevron,
            o.Fletero,
            o.Invoice,
            o.Programado,
            o.created_at,
            CodigoProveedorSAP, 
            f.CodigoSAP codigoTransporte,
            p.nombre NombrePlanta,
            f.NumeroUnidad,
            o.idFlete,
            o.nombre_vendedor,
            o.vendedor,
            o.contra_boleta
          from au_order o
          left join au_flete f on o.idFlete=f.id
          INNER JOIN au_order_status ou
          on o.au_order_status_id=ou.id
          INNER JOIN au_tipo_pago tp
          on o.TipoPago=tp.id
          INNER JOIN au_planta p
          ON o.au_planta_id=p.id
          WHERE 
		  --DATEPART(m, o.FechaCarga) >= DATEPART(m, DATEADD(m, -1, getdate()))
          --AND DATEPART(yyyy, o.FechaCarga) = DATEPART(yyyy, DATEADD(m, -1, getdate()))
		  (year(o.FechaCarga) * 100) + month(o.FechaCarga) >= (year(DATEADD(m, -1, getdate())) * 100) + month(DATEADD(m, -1, getdate()))
          and o.au_order_status_id<5
          order by o.created_at desc";
    $orders = DB::connection('sqlsrv')->select($sql);

    if(!empty($orders))
    foreach($orders as &$o):
      $cod = trim($o->CodigoCliente);
      $o->CodigoClienteSap = $cod;
      $sql = "select
                CardName+' - '+ CardCode label,
                QryGroup1 star,
                NumSAP = b.GroupNum,
                CantDias = (b.ExtraMonth * 30) + b.ExtraDays 
              from
                OCRD a inner join
                OCTG b on a.GroupNum = b.GroupNum
              WHERE
                CARDTYPE='C'
              and    
                CardCode='".$o->CodigoCliente."'";
      $res = DB::connection('sqlsrv2')->select($sql);

      if(!empty($res)):
        $o->CodigoCliente = $res[0]->label;
        $o->star = $res[0]->star;
        $o->NumSAP = $res[0]->NumSAP;
      endif;

      // Search if the code exists
      $sql = "select 
                1
              from 
                OCRD
              where 
                frozenFor='N'
              and 
                CardCode='".$o->CodigoProveedorSAP."'";
      $o->CodigoProveedorSAPExist = count(DB::connection('sqlsrv2')->select($sql));

      $sql = "select 
                1
              from 
                OCRD
              where 
                frozenFor='N'
              and 
                CardCode='".$o->codigoTransporte."'";
      $o->codigoTransporteExist = count(DB::connection('sqlsrv2')->select($sql));
      
      $sql = "select 
                1
              from 
                OCRD
              where 
                frozenFor='N'
              and 
                CardCode='".$o->CodigoClienteSap."'";
      $o->CodigoClienteSapExist = count(DB::connection('sqlsrv2')->select($sql));

      // Get the order detail info
      $sql = "select
          1
        from
          au_order_detail
        where
          PreciosNoOficiales=1
        and 
          au_order_id=".$o->id;
      $o->noOficiales = count(DB::connection('sqlsrv')->select($sql));
    endforeach;

    usort($orders,'cmp');

    return $orders;
});

Route::post('/get-orders-programar', function (Request $request) {
  $sql = "select  o.id,
            o.CodigoCliente,
            RTRIM(LTRIM(tp.tipo)) tipo_pago,
            RTRIM(LTRIM(tp.tipo)) NumSAPLabel,
            tp.id id_tp,
            o.Direccion,
            o.FleteAplicado,
            o.FleteXGalon,
            f.CodigoSAP flete,
            f.Placa placa,
            o.Comentarios,
            ou.status,
            CONVERT(VARCHAR(8),
            o.FechaCarga,3) FechaCarga,
            o.FechaCarga fecha_carga,
            CONVERT(varchar(15),
            o.HoraCarga,108) HoraCarga, 
            CONVERT(VARCHAR(8),o.created_at,3) creado,
            o.au_planta_id planta,
            o.au_order_status_id sid,
            o.HorarioAsignado,
            o.CreditoValidado, 
            o.Chevron,
            o.Fletero,
            o.Invoice,
            o.Programado,
            o.created_at,
            CodigoProveedorSAP, 
            f.CodigoSAP codigoTransporte,
            p.nombre NombrePlanta,
            f.NumeroUnidad,
            o.idFlete,
            o.nombre_vendedor,
            o.vendedor,
            o.contra_boleta
          from au_order o
          left join au_flete f on o.idFlete=f.id
          INNER JOIN au_order_status ou
          on o.au_order_status_id=ou.id
          INNER JOIN au_tipo_pago tp
          on o.TipoPago=tp.id
          INNER JOIN au_planta p
          ON o.au_planta_id=p.id
          WHERE o.au_order_status_id<5
          order by o.id desc";
    $orders = DB::connection('sqlsrv')->select($sql);


    // $sql = "select  id value,
    //                 tipo label,
    //                 sap,
    //                 dias
    //         from    au_tipo_pago";
    // $tipos_pago = DB::connection('sqlsrv')->select($sql);

    if(!empty($orders))
    foreach($orders as &$o):
      $cod = trim($o->CodigoCliente);
      $o->CodigoClienteSap = $cod;
      $sql = "select
                CardName+' - '+ CardCode label,
                QryGroup1 star,
                NumSAP = b.GroupNum,
                CantDias = (b.ExtraMonth * 30) + b.ExtraDays 
              from
                OCRD a inner join
                OCTG b on a.GroupNum = b.GroupNum
              WHERE
                CARDTYPE='C'
              and    
                CardCode='".$o->CodigoCliente."'";
      $res = DB::connection('sqlsrv2')->select($sql);

      if(!empty($res)):
        $o->CodigoCliente = $res[0]->label;
        $o->star = $res[0]->star;
        $o->NumSAP = $res[0]->NumSAP;

        // $sql = "select  id value,
        //                 tipo label,
        //                 sap
        //         from    au_tipo_pago
        //         where   dias <= ".$res[0]->CantDias;
        // $o->tipos_pago = DB::connection('sqlsrv')->select($sql);


        // $tpago = [];

        // if(!empty($tipos_pago))
        // foreach($tipos_pago as $tp):
        //   if($tp->dias <= $res[0]->CantDias):
        //     $tpago[] = $tp;
        //   endif;
        // endforeach;

        // $o->tipos_pago = $tpago;

        // foreach($o->tipos_pago as $t):
        //   if($t->value == $o->id_tp):
        //     $o->NumSAP = $t->value;
        //     $o->NumSAPLabel = $t->label;
        //   endif;
        // endforeach;
      endif;

      $sql = "SELECT od.cantidad,
                    od.Compartimiento,
                    p.Nombre,
                    p.Color,
                    Cast(od.idp AS DECIMAL(18, 2))    IDP,
                    Cast(od.costo AS DECIMAL(18, 2))  Costo,
                    Cast(od.precio AS DECIMAL(18, 2)) Precio
              FROM   au_order_detail od
                    INNER JOIN au_producto p
                            ON od.producto = p.codigosap
              WHERE  od.au_order_id = ".$o->id."
              ORDER  BY compartimiento ASC";
      $o->Compartimientos = DB::connection('sqlsrv')->select($sql);

      $detail = "";
      $costo = 0;
      $venta = 0;
      foreach($o->Compartimientos as $c):
        $detail .= "<strong>Comp: ".$c->Compartimiento." - ".$c->Nombre . ":</strong> ".$c->cantidad."<br>";
        $costo += $c->Costo*$c->cantidad;
        $venta += $c->Precio*$c->cantidad;
      endforeach;

      $o->costo = $costo;
      $o->venta = $venta;
      $o->detalle= $detail;
      
      // $sql = "select 
      //             c.created_at,
      //             c.updated_at,
      //             u.name,
      //             c.Comentario comentario
      //         from 
      //           au_order_comments c
      //           INNER JOIN cms_users u
      //           ON c.cms_users_id=u.id
      //         where c.au_order_id=".$o->id."
      //         order by c.created_at desc";
      // $o->comentarios = DB::connection('sqlsrv')->select($sql);

      // $sql = 'select	
      //             CONVERT(VARCHAR(8),l.created_at,3) fecha,
      //             convert(char(5), l.created_at, 108) hora,
      //             u.name,
      //             t.type,
      //             l.Texto,
      //             l.id
      //         from  
      //           au_log l
      //           INNER JOIN cms_users u
      //             ON l.cms_users_id=u.id
      //           INNER JOIN au_log_type t
      //             ON l.au_log_type_id = t.id
      //         where "Order" = '.$o->id;
      // $o->log = DB::connection('sqlsrv')->select($sql);

      // $sql = "select 
      //           TOP 1 ShortName,
      //           RefDate fecha,
      //           Ref1 documento,
      //           BalDueDeb debit,
      //           BalDueCred credit
      //         from 
      //           JDT1
      //         where 
      //           ShortName = '".$cod."'
      //         AND 
      //           BalDueDeb + BalDueCred > 0";
      // $o->credit = DB::connection('sqlsrv2')->select($sql)[0];
      // $sql = "select
      //           a.CardCode,
      //           a.CardName,
      //           a.CreditLine,
      //           a.Balance,
      //           b.TransType,
      //           b.Ref1,
      //           CONVERT(VARCHAR(20),b.RefDate,103) Fecha_Emision,
      //           CONVERT(VARCHAR(20),b.DueDate,103) Fecha_Vencimiento,
      //           Monto_Original = b.Debit - Credit,
      //           Monto_Vencido = b.BalDueDeb + b.BalDueCred 
      //         from
      //           OCRD a inner join
      //           JDT1 b on a.CardCode = b.ShortName
      //         where
      //           CardCode = '".$cod."'
      //           and b.BalDueCred + b.BalDueDeb > 0";
      // $o->credit_detail = DB::connection('sqlsrv2')->select($sql);

      // Search if the code exists
      // $sql = "select 
      //           1
      //         from 
      //           OCRD
      //         where 
      //           frozenFor='N'
      //         and 
      //           CardCode='".$o->CodigoProveedorSAP."'";
      // $o->CodigoProveedorSAPExist = count(DB::connection('sqlsrv2')->select($sql));

      // $sql = "select 
      //           1
      //         from 
      //           OCRD
      //         where 
      //           frozenFor='N'
      //         and 
      //           CardCode='".$o->codigoTransporte."'";
      // $o->codigoTransporteExist = count(DB::connection('sqlsrv2')->select($sql));
      
      // $sql = "select 
      //           1
      //         from 
      //           OCRD
      //         where 
      //           frozenFor='N'
      //         and 
      //           CardCode='".$o->CodigoClienteSap."'";
      // $o->CodigoClienteSapExist = count(DB::connection('sqlsrv2')->select($sql));
    endforeach;

    usort($orders,'cmp');

	return $orders;
});

function cmp($a, $b)
{
    if ($a->star == $b->star) {
      if (strtotime($a->created_at) == strtotime($b->created_at)) {
          return 0;
      }
      return (strtotime($a->created_at) < strtotime($b->created_at)) ? -1 : 1;
    }
    return ($a->star < $b->star) ? 1 : -1;
}

//We save an order
Route::post('/save-order', function (Request $request) {
    
    $comentario = "";
    if(!empty($request->comentario)) $comentario = $request->comentario;

    $dataArray = [
        'CodigoCliente' => $request->cliente,
        'Comentarios' =>$comentario,
        'Direccion' =>$request->direccion,
        'FleteAplicado' =>$request->fleteAplicado,
        'FleteXGalon' =>$request->montoPorGalon,
        'TipoPago' =>$request->tipoPago,
        'idFlete' =>$request->transporte,
        'FechaCarga' =>$request->anioEntrega."-".$request->mesEntrega."-".$request->diaEntrega,
        'HoraCarga' =>$request->hora.":".$request->minutos,
        'au_order_status_id'=>1,
        'created_at' => get_current_time(),
        'updated_at' => get_current_time(),
        'NombreCliente'=>$request->nombreCliente,
        'generar_entrada_mercancia'=>$request->entrada_mercancia,
        'generar_factura'=>$request->factura,
        'vendedor'=>$request->vendedor,
        'nombre_vendedor'=>$request->nombre_vendedor,
        'au_planta_id'=>$request->planta
    ];

    $id = DB::table('au_order')->insertGetId($dataArray);

    $detalle = $request->detalle;

    foreach($detalle as $d):
        $dataArray = [
            'au_order_id' =>$id,
            'Producto' => $d[0],
            'Cantidad' => $d[1],
            'Compartimiento'=>$d[2],
            'precio'=>$d[6],
            'PreciosNoOficiales'=>$d[5],
            'Flete'=>$request->fleteAplicado,
            'IDP'=>$d[8],
            'Costo'=>$d[7],
            'au_flete_id'=>$request->transporte,
            'created_at' => get_current_time(),
            'updated_at' => get_current_time()
        ];
        DB::table('au_order_detail')->insert($dataArray);
    endforeach;

    $dataArray = [
      'Texto' => "Se creó una nueva orden",
      'au_log_type_id'=>4,
      'Order' => $id,
      'cms_users_id' => $request->user,
      'created_at' => get_current_time()
    ];
    $id = DB::table('au_log')->insertGetId($dataArray);

    return $request;
	// return $fletes;
});
//We assigned an order
Route::post('/assign-order', function (Request $request) {
    
    $order = $request->id;
    $user = $request->user;
    $comentario = $request->comentario;

    $dataArray = [
        'idFlete' =>$request->transporte,
        'FechaCarga' =>$request->anioEntrega."-".$request->mesEntrega."-".$request->diaEntrega,
        'HoraCarga' =>$request->hora.":".$request->minutos,
        'au_order_status_id'=>2,
        'created_at' => get_current_time(),
        'updated_at' => get_current_time(),
        'au_planta_id'=>$request->planta,
        'FleteXGalon' => $request->montoPorGalon,
        'HorarioAsignado'=>1
    ];

    $change = "<br>";
    // We check if the plant changed
    if($request->planta!=$request->planta_original):
      $change .= "Se cambió la planta de <strong>".$request->planta_label."</strong> a <strong>".$request->planta_original_label."</strong>  <br>";
    endif;
    // We check if the transporte changed
    if($request->transporte!=$request->transporte_original):
      $change .= "Se cambió la planta de <strong>".$request->transporte_label."</strong> <strong>a ".$request->transporte_original_label."</strong>  <br>";
    endif;
    // We check if the hour changed
    if($request->horaOriginal!=$request->hora || $request->minutos!=$request->minutosOriginal):
      $change .= "Se cambió la hora de carga de <strong>".$request->horaOriginal.":".addZero($request->minutosOriginal)."</strong> <strong>a ".$request->hora.":".addZero($request->minutos)."</strong>  <br>";
    endif;
    // We check if the date changed
    if($request->diaEntrega!=$request->diaEntregaOriginal || $request->mesEntrega!=$request->mesEntregaOriginal || $request->anioEntrega !=$request->anioEntregaOriginal):
      $change .= "Se cambió la fecha de carga de <strong>".$request->diaEntregaOriginal."/".$request->mesEntregaOriginal."/".$request->anioEntregaOriginal."</strong> <strong>a ".$request->diaEntrega."/".$request->mesEntrega."/".$request->anioEntrega."</strong>  <br>";
    endif;
    // We check if the monto por galon changed
    if($request->montoPorGalon!=$request->montoPorGalonOriginal):
      $change .= "Se cambió el monto por galon (flete) de <strong>".$request->montoPorGalonOriginal."</strong> a <strong>".$request->montoPorGalon."</strong>  <br>";
    endif;


    DB::table('au_order')
            ->where('id', $order)
            ->update($dataArray);

    if(!empty($comentario)):
        $dataArray = [
            'au_order_id' =>$order,
            'Comentario' =>$comentario,
            'created_at' => get_current_time(),
            'updated_at' => get_current_time(),
            'cms_users_id' => $user
        ];

        DB::table('au_order_comments')->insert($dataArray); 
        
        $dataArray = [
          'Texto' => "Agregó un nuevo comentario a la orden",
          'au_log_type_id'=>4,
          'Order' => $order,
          'cms_users_id' => $user,
          'created_at' => get_current_time()
        ];
        $id = DB::table('au_log')->insertGetId($dataArray);
    endif;

    $dataArray = [
      'Texto' => "Se asignó un horario a la orden".$change,
      'au_log_type_id'=>4,
      'Order' => $order,
      'cms_users_id' => $user,
      'created_at' => get_current_time()
    ];
    $id = DB::table('au_log')->insertGetId($dataArray);

    return $request;
	// return $fletes;
});
function addZero($num){
  if(strlen($num)<2){
    return "0".$num;
  }
  return $num;
}
//We approve client credit
Route::post('/approve-order', function (Request $request) {
    
    $order = $request->id;
    $user = $request->user;
    $comentario = $request->comentario;
    $contra_boleta = $request->contra_boleta;

    $credito_validado = 1;

    if($op==2):
      $credito_validado = 0;
    endif;


    $dataArray = [
        'au_order_status_id'=>4,
        'updated_at' => get_current_time(),
        'TipoPago' =>$request->tipoPago,
        'CreditoValidado'=>$credito_validado,
        'contra_boleta'=>$contra_boleta,
    ];

    DB::table('au_order')
            ->where('id', $order)
            ->update($dataArray);

    if(!empty($comentario)):
        $dataArray = [
            'au_order_id' =>$order,
            'Comentario' =>$comentario,
            'created_at' => get_current_time(),
            'updated_at' => get_current_time(),
            'cms_users_id' => $user
        ];

        DB::table('au_order_comments')->insert($dataArray);   
        
        $dataArray = [
          'Texto' => "Agregó un nuevo comentario a la orden",
          'au_log_type_id'=>4,
          'Order' => $order,
          'cms_users_id' => $user,
          'created_at' => get_current_time()
        ];
        $id = DB::table('au_log')->insertGetId($dataArray);
    endif;

    // We check if the monto por galon changed
    if($request->tipoPago!=$request->tipoPagoOriginal):
      $change .= "Se cambió el Tipo de Pago de <strong>".$request->tipoPagoOriginal_label."</strong> a <strong>".$request->tipoPago_label."</strong>  <br>";
    endif;

    $texto = "Se aprobó el crédito de la orden<br>".$change;

    if($contra_boleta==2):
      $texto = "Se necesita aprobar el crédito contra boleta<br>".$change;
    elseif($contra_boleta==3):
      $texto = "Se aprobó el crédito contra boleta<br>".$change;
    endif;

    $dataArray = [
      'Texto' => $texto,
      'au_log_type_id'=>4,
      'Order' => $order,
      'cms_users_id' => $user,
      'created_at' => get_current_time()
    ];
    $id = DB::table('au_log')->insertGetId($dataArray);

    return $request;
	// return $fletes;
});
//We cancel the order
Route::post('/cancel-order', function (Request $request) {
    
    $order = $request->id;
    $user = $request->user;
    $comentario = $request->comentario;

    $dataArray = [
        'au_order_status_id'=>6,
        'updated_at' => get_current_time()
    ];

    DB::table('au_order')
            ->where('id', $order)
            ->update($dataArray);

    $dataArray = [
      'Texto' => "Se canceló la orden.",
      'au_log_type_id'=>4,
      'Order' => $order,
      'cms_users_id' => $user,
      'created_at' => get_current_time()
    ];
    $id = DB::table('au_log')->insertGetId($dataArray);

    if(!empty($comentario)):
        $dataArray = [
            'au_order_id' =>$order,
            'Comentario' =>$comentario,
            'created_at' => get_current_time(),
            'updated_at' => get_current_time(),
            'cms_users_id' => $user
        ];

        DB::table('au_order_comments')->insert($dataArray);  
        
        $dataArray = [
          'Texto' => "Agregó un nuevo comentario a la orden",
          'au_log_type_id'=>4,
          'Order' => $order,
          'cms_users_id' => $user,
          'created_at' => get_current_time()
        ];
        $id = DB::table('au_log')->insertGetId($dataArray);
    endif;

    return $request;
	// return $fletes;
});

//We mark the order as programmed
Route::post('/mark-programmed', function (Request $request) {
    
    $order = $request->id;
    $user = $request->user;
    $comentario = $request->comentario;

    $dataArray = [
        'Programado'=>1,
        'updated_at' => get_current_time()
    ];

    DB::table('au_order')
            ->where('id', $order)
            ->update($dataArray);

    $dataArray = [
      'Texto' => "Se programó la orden.",
      'au_log_type_id'=>4,
      'Order' => $order,
      'cms_users_id' => $user,
      'created_at' => get_current_time()
    ];
    $id = DB::table('au_log')->insertGetId($dataArray);

    if(!empty($comentario)):
        $dataArray = [
            'au_order_id' =>$order,
            'Comentario' =>$comentario,
            'created_at' => get_current_time(),
            'updated_at' => get_current_time(),
            'cms_users_id' => $user
        ];

        DB::table('au_order_comments')->insert($dataArray);    
        $dataArray = [
          'Texto' => "Agregó un nuevo comentario a la orden",
          'au_log_type_id'=>4,
          'Order' => $order,
          'cms_users_id' => $user,
          'created_at' => get_current_time()
        ];
        $id = DB::table('au_log')->insertGetId($dataArray);
    endif;

    return $request;
	// return $fletes;
});

//We push the order to SAP
Route::post('/push-order-sap', function (Request $request) {
    
    $order = $request->id;
    $user = $request->user;
    $comentario = $request->comentario;

    // Add the comment if any
    if(!empty($comentario)):
      $dataArray = [
          'au_order_id' =>$order,
          'Comentario' =>$comentario,
          'created_at' => get_current_time(),
          'updated_at' => get_current_time(),
          'cms_users_id' => $user
      ];

      DB::table('au_order_comments')->insert($dataArray); 
      
      $dataArray = [
        'Texto' => "Agregó un nuevo comentario a la orden",
        'au_log_type_id'=>4,
        'Order' => $order,
        'cms_users_id' => $user,
        'created_at' => get_current_time()
      ];
      $id = DB::table('au_log')->insertGetId($dataArray);
    endif;

    // ####################################################
    // Order upload to SAP
    // ####################################################
    // We get the order information 
    $sql = "select  
                codigoCliente, 
                CodigoProveedorSAP, 
                f.CodigoSAP codigoTransporte,
                o.id,FleteXGalon,
                o.FechaCarga,
                t.sap sap_tp,
                o.FleteAplicado, 
                f.NumeroUnidad nunidad,
                o.direccion,
                o.vendedor,
                o.contra_boleta,
                o.generar_entrada_mercancia,
                o.generar_factura
            from    au_order o, au_flete f,au_planta p, au_tipo_pago t
            where   o.idFlete = f.id
            and     o.au_planta_id=p.id
            and     o.TipoPago=t.id
            and     o.id=".$order;
    $orders = DB::connection('sqlsrv')->select($sql);

    // Obentemos el nombre del cliente
    $sql = "select CardName nombre,U_NIT nit, CardFName razonsocial
            from OCRD
            where CARDTYPE='C'
            and CardCode = '".$orders[0]->codigoCliente."'";
    $cliente = DB::connection('sqlsrv2')->select($sql);

    // Obentemos el nombre de chevron
    $sql = "select CardName nombre,U_NIT nit
            from OCRD
            where CardCode = '".$orders[0]->CodigoProveedorSAP."'";
    $chevron = DB::connection('sqlsrv2')->select($sql);

    // Obentemos el nombre de flete
    $sql = "select CardName nombre,U_NIT nit
            from OCRD
            where CardCode = '".$orders[0]->codigoTransporte."'";
    $fletero = DB::connection('sqlsrv2')->select($sql);

    $oDetail = null;
    if(!empty($orders)):
      // $sql = "select Producto,
      //                 Cantidad, 
      //                 precio, 
      //                 Costo,
      //                 IDPSAP,
      //                 p.IDP,
      //                 p.FleteSAP
      //         from au_order_detail o, au_producto p
      //         where o.au_order_id=".$order."
      //         and p.codigoSAP = o.Producto
      //         order by o.Producto asc";
      $sql = "select Producto,
                    sum(Cantidad) Cantidad, 
                    max(precio) precio, 
                    max(Costo) Costo,
                    max(IDPSAP) IDPSAP,
                    max(p.IDP) IDP,
                    max(p.FleteSAP) FleteSAP
              from au_order_detail o, au_producto p
              where o.au_order_id=".$order."
              and p.codigoSAP = o.Producto
              group by Producto
              order by o.Producto asc";
        $oDetail = DB::connection('sqlsrv')->select($sql);
    endif;

    $service = new DiServerServicesSample();
    $login= new Login();
    
    //Local dev
    $login->DataBaseServer="WIN-UFG8LH8HP0A";
    $login->DataBaseName="Empresa03";
    $login->DataBaseType="dst_MSSQL2008";
    $login->DataBaseUserName="sa"; // string
    $login->DataBasePassword="\$Aumenta2019#"; // string
    $login->CompanyUserName="manager"; // string
    $login->CompanyPassword="1234"; // string
    $login->Language="ln_English"; // string
    $login->LicenseServer= "192.168.1.53:30000"; // Change HOST and port

    #Texpetrol
    // $login->DataBaseServer="192.168.168.9";
    // $login->DataBaseName="Empresa03";
    // $login->DataBaseType="dst_MSSQL2008";
    // $login->DataBaseUserName="sa"; // string
    // $login->DataBasePassword="Mobil2011"; // string
    // $login->CompanyUserName="it"; // string
    // $login->CompanyPassword="pa55w04d"; // string
    // $login->Language="ln_English"; // string
    // $login->LicenseServer= "192.168.168.9:30000"; // Change HOST and port


    // Call to Login Service with the $login Object
    $IdSession=$service->Login($login)->LoginResult;
/******************************************************************************** */
    // We add the PO del proveedor
    $AddPurchaseOrder= new AddPurchaseOrder();
    $AddPurchaseOrder->SessionID=$IdSession;

    $xmlHeader = "";
    $xmlHeader = $xmlHeader."<DocDate>".get_current_date($orders[0]->FechaCarga)."</DocDate>";
    // $xmlHeader = $xmlHeader."<DocDueDate>".get_current_date($orders[0]->FechaCarga)."</DocDueDate>";
    $xmlHeader = $xmlHeader."<CardCode>".$orders[0]->CodigoProveedorSAP."</CardCode>";
    $xmlHeader = $xmlHeader."<U_FacNit>".$chevron[0]->nit."</U_FacNit>";
    $xmlHeader = $xmlHeader."<U_FacNom><![CDATA[".$chevron[0]->nombre."]]></U_FacNom>";
    $xmlHeader = $xmlHeader."<U_Transporte>".$orders[0]->nunidad."</U_Transporte>";
    $xmlHeaderComposed="<Documents><row>".$xmlHeader."</row></Documents>";

    // We compose order lines
    $orderLines="";
    $prod = trim($oDetail[0]->Producto);
    $cant = 0;
    $afterVat = $oDetail[0]->Costo-$oDetail[0]->IDP;
    $IDPSAP = $oDetail[0]->IDPSAP;
    $IDP = $oDetail[0]->IDP;
    foreach ($oDetail as $lin):
      //Actualizamos el producto y reiniciamos la cantidad
      $prod = trim($lin->Producto);
      $cant = intval($lin->Cantidad);   
      $afterVat = $lin->Costo - $lin->IDP;
      $IDPSAP = $lin->IDPSAP;
      $IDP = $lin->IDP;
        $orderLines= $orderLines."<row>";
        $orderLines= $orderLines."<ItemCode>".$prod."</ItemCode>";
        $orderLines= $orderLines."<Quantity>".$cant."</Quantity>";
        $orderLines= $orderLines."<PriceAfterVAT>".$afterVat."</PriceAfterVAT>";
        $orderLines= $orderLines."<TaxCode>IVA</TaxCode>";
        $orderLines= $orderLines."</row>";

        // IDP
        $orderLines= $orderLines."<row>";
        $orderLines= $orderLines."<ItemCode>".$IDPSAP."</ItemCode>";
        $orderLines= $orderLines."<Quantity>".$cant."</Quantity>";
        $orderLines= $orderLines."<Price>".$IDP."</Price>";
        $orderLines= $orderLines."<TaxCode>EXE</TaxCode>";
        $orderLines= $orderLines."</row>";
    endforeach;

    $xmlorderLinesComposed="<Document_Lines>".$orderLines."</Document_Lines>";

    $xmlOrderComposed="<BOM xmlns='http://www.sap.com/SBO/DIS'><BO><AdmInfo><Object>oPurchaseDeliveryNotes</Object></AdmInfo>".$xmlHeaderComposed.$xmlorderLinesComposed."</BO></BOM>";

    $AddPurchaseOrder->sXmlOrderObject=$xmlOrderComposed;

    // var_dump($AddPurchaseOrder);

    // return $xmlOrderComposed;

    if($orders[0]->generar_entrada_mercancia=="1"):
      $result=$service->AddPurchaseOrder($AddPurchaseOrder);
      // echo $result->AddPurchaseOrderResult->any;
      
      $result = simplexml_load_string($result->AddPurchaseOrderResult->any,'SimpleXMLElement', LIBXML_NOCDATA);
    endif;
    // return $result;

    if((empty($result) && $orders[0]->generar_entrada_mercancia!="1") || !empty($result->children())):
      // echo $result->RetKey;
      // We insert the sap order to the log  
      $sapOrder = 0;
      if(!empty($result)):
        $sapOrder = ((array)$result->RetKey)[0];
        $sql = "select DocNum
                from OPDN
                where DocEntry = ".$sapOrder;
        $sapOrder = DB::connection('sqlsrv2')->select($sql)[0]->DocNum;
        $dataArray = [
            'Texto' => "Goods Receipt PO #".$sapOrder." Chevron",
            'au_log_type_id'=>3,
            'Order' => $order,
            'cms_users_id' => $user,
            'created_at' => get_current_time()
        ];
        $id = DB::table('au_log')->insertGetId($dataArray);
      endif;

/******************************************************************************** */
// PO FLETE
      $result = "";
      if($orders[0]->FleteAplicado==2):
        $xmlHeader = "";
        $xmlHeader = $xmlHeader."<DocDate>".get_current_date($orders[0]->FechaCarga)."</DocDate>";
        // $xmlHeader = $xmlHeader."<DocDueDate>".get_current_date($orders[0]->FechaCarga)."</DocDueDate>";
        $xmlHeader = $xmlHeader."<CardCode>".$orders[0]->codigoTransporte."</CardCode>";
        $xmlHeader = $xmlHeader."<U_FacNit>".$fletero[0]->nit."</U_FacNit>";
        $xmlHeader = $xmlHeader."<U_FacNom><![CDATA[".$fletero[0]->nombre."]]></U_FacNom>";
        $xmlHeader = $xmlHeader."<U_Transporte>".$orders[0]->nunidad."</U_Transporte>";
        // $xmlHeader = $xmlHeader."<DocType>dDocument_Service</DocType>";
        $xmlHeaderComposed="<Documents><row>".$xmlHeader."</row></Documents>";

        // We compose order lines
        $orderLines="";
        foreach ($oDetail as $lin) {
          $orderLines= $orderLines."<row>";
          // $orderLines= $orderLines."<ItemCode>".trim($lin->IDPSAP)."</ItemCode>";
          $orderLines= $orderLines."<ItemCode>".trim($lin->FleteSAP)."</ItemCode>";
          $orderLines= $orderLines."<Quantity>".$lin->Cantidad."</Quantity>";
          $orderLines= $orderLines."<PriceAfterVAT>".($orders[0]->FleteXGalon+0)."</PriceAfterVAT>";
          $orderLines= $orderLines."<TaxCode>IVA</TaxCode>";
          $orderLines= $orderLines."</row>";
        }
        $xmlorderLinesComposed="<Document_Lines>".$orderLines."</Document_Lines>";

        // We insert a purchase order Delivery
        $xmlOrderComposed="<BOM xmlns='http://www.sap.com/SBO/DIS'><BO><AdmInfo><Object>oPurchaseDeliveryNotes</Object></AdmInfo>".$xmlHeaderComposed.$xmlorderLinesComposed."</BO></BOM>";
        $AddPurchaseOrder->sXmlOrderObject=$xmlOrderComposed;

        // echo $xmlOrderComposed;

        $result=$service->AddPurchaseOrder($AddPurchaseOrder);
        // echo $result->AddPurchaseOrderResult->any;

        $result = simplexml_load_string($result->AddPurchaseOrderResult->any,'SimpleXMLElement', LIBXML_NOCDATA);
      endif;
      
      if($orders[0]->FleteAplicado==1 || !empty($result->children())):
        if(!empty($result)):
          // We insert the invoice
          $sapDelivery = ((array)$result->RetKey)[0];

          $sql = "select DocNum
                  from OPDN
                  where DocEntry = ".$sapDelivery;
          $sapDelivery = DB::connection('sqlsrv2')->select($sql)[0]->DocNum;

          $dataArray = [
            'Texto' => "Goods Receipt PO #".$sapDelivery." Fletero",
            'au_log_type_id'=>3,
            'Order' => $order,
            'cms_users_id' => $user,
            'created_at' => get_current_time()
          ];
          $id = DB::table('au_log')->insertGetId($dataArray);
        endif;
/******************************************************************************** */
          // We insert the invoice
          $xmlHeader = "";
          $xmlHeader = $xmlHeader."<DocDate>".get_current_date($orders[0]->FechaCarga)."</DocDate>";
          // $xmlHeader = $xmlHeader."<DocDueDate>".get_current_date($orders[0]->FechaCarga)."</DocDueDate>";
          $xmlHeader = $xmlHeader."<CardCode>".$orders[0]->codigoCliente."</CardCode>";
          $xmlHeader = $xmlHeader."<Comments>".$comentario."</Comments>";
          $xmlHeader = $xmlHeader."<PaymentGroupCode>".$orders[0]->sap_tp."</PaymentGroupCode>";
          $xmlHeader = $xmlHeader."<SalesPersonCode>".$orders[0]->vendedor."</SalesPersonCode>";
          $xmlHeader = $xmlHeader."<Address2><![CDATA[".$orders[0]->direccion."]]></Address2>";
          $xmlHeader = $xmlHeader."<U_Transporte>".$orders[0]->nunidad."</U_Transporte>";
          $xmlHeader = $xmlHeader."<U_FacNit>".$cliente[0]->nit."</U_FacNit>";
          $xmlHeader = $xmlHeader."<U_FacNom><![CDATA[".$cliente[0]->razonsocial."]]></U_FacNom>";
          $xmlHeaderComposed="<Documents><row>".$xmlHeader."</row></Documents>";

          $orderLines="";

          foreach ($oDetail as $lin):
            $prod = trim($lin->Producto);
            $cant = intval($lin->Cantidad);
            $IDPSAP = $lin->IDPSAP;
            $IDP = $lin->IDP;
            $FLETESAP = trim($lin->FleteSAP);
            $precio = $lin->precio - $lin->IDP -$orders[0]->FleteXGalon;

              $orderLines= $orderLines."<row>";
              $orderLines= $orderLines."<ItemCode>".$prod."</ItemCode>";
              $orderLines= $orderLines."<Quantity>".$cant."</Quantity>";
              $orderLines= $orderLines."<PriceAfterVAT>".$precio."</PriceAfterVAT>";
              $orderLines= $orderLines."<TaxCode>IVA</TaxCode>";
              $orderLines= $orderLines."</row>";
        
              // IDP
              $orderLines= $orderLines."<row>";
              $orderLines= $orderLines."<ItemCode>".$IDPSAP."</ItemCode>";
              $orderLines= $orderLines."<Quantity>".$cant."</Quantity>";
              $orderLines= $orderLines."<Price>".$IDP."</Price>";
              $orderLines= $orderLines."<TaxCode>EXE</TaxCode>";
              $orderLines= $orderLines."</row>";

              // Si tiene flete activado
              if($orders[0]->FleteAplicado==2):
                // Flete
                $orderLines= $orderLines."<row>";
                $orderLines= $orderLines."<ItemCode>".$FLETESAP."</ItemCode>";
                $orderLines= $orderLines."<Quantity>".$cant."</Quantity>";
                $orderLines= $orderLines."<PriceAfterVAT>".($orders[0]->FleteXGalon+0)."</PriceAfterVAT>";
                $orderLines= $orderLines."<TaxCode>IVA</TaxCode>";
                $orderLines= $orderLines."</row>";
              endif;

          endforeach;
          
          $xmlorderLinesComposed="<Document_Lines>".$orderLines."</Document_Lines>";

          if($orders[0]->generar_factura=="0"):
            $xmlOrderComposed="<BOM xmlns='http://www.sap.com/SBO/DIS'><BO><AdmInfo><Object>oDeliveryNotes</Object></AdmInfo>".$xmlHeaderComposed.$xmlorderLinesComposed."</BO></BOM>";
          else:
            $xmlOrderComposed="<BOM xmlns='http://www.sap.com/SBO/DIS'><BO><AdmInfo><Object>oInvoices</Object></AdmInfo>".$xmlHeaderComposed.$xmlorderLinesComposed."</BO></BOM>";
          endif;

          $AddPurchaseOrder->sXmlOrderObject=$xmlOrderComposed;

          // echo $xmlOrderComposed;
          // return;

          $result=$service->AddPurchaseOrder($AddPurchaseOrder);
          // echo $result->AddPurchaseOrderResult->any;
          $result = simplexml_load_string($result->AddPurchaseOrderResult->any,'SimpleXMLElement', LIBXML_NOCDATA);

          // return "";
          if(!empty($result->children())):
            // Insertamos el invoice
            $sapInvoice = ((array)$result->RetKey)[0];

            $sql = "select DocNum
                    from OINV
                    where DocEntry = ".$sapInvoice;
            $sapInvoice = DB::connection('sqlsrv2')->select($sql)[0]->DocNum;

            $dataArray = [
              'Texto' => "Invoice #".$sapInvoice,
              'au_log_type_id'=>3,
              'Order' => $order,
              'cms_users_id' => $user,
              'created_at' => get_current_time()
            ];
            $id = DB::table('au_log')->insertGetId($dataArray);

            // Update the order status
            $dataArray = [
              'au_order_status_id'=>5,
              'updated_at' => get_current_time(),
              'Chevron' => $sapOrder,
              'Fletero' => $sapDelivery,
              'Invoice' => $sapInvoice
            ];

            DB::table('au_order')
                    ->where('id', $order)
                    ->update($dataArray);

            $dataArray = [
                'Texto' => "Se subió la orden a SAP",
                'au_log_type_id'=>4,
                'Order' => $order,
                'cms_users_id' => $user,
                'created_at' => get_current_time()
            ];
            $id = DB::table('au_log')->insertGetId($dataArray);

            // Return true
            return json_encode([true, $sapOrder, $sapDelivery,$sapInvoice]);
          else:
            $error = ((array)$result->children('env', true)->Body->Fault->Reason->Text)[0];
            $dataArray = [
                'Texto' => $error,
                'au_log_type_id'=>2,
                'Order' => $order,
                'cms_users_id' => $user,
                'created_at' => get_current_time()
            ];
            $id = DB::table('au_log')->insertGetId($dataArray);
            $ret = [
              false,
              $error
            ];
            return json_encode($ret);
          endif;
      else:
        $error = ((array)$result->children('env', true)->Body->Fault->Reason->Text)[0];
        $dataArray = [
            'Texto' => $error,
            'au_log_type_id'=>2,
            'Order' => $order,
            'cms_users_id' => $user,
            'created_at' => get_current_time()
        ];
        $id = DB::table('au_log')->insertGetId($dataArray);

        $ret = [
          false,
          $error
        ];
        return json_encode($ret);
      endif;
  else:
      $error = ((array)$result->children('env', true)->Body->Fault->Reason->Text)[0];
      // We insert the sap error to the log  
      $dataArray = [
          'Texto' => $error,
          'au_log_type_id'=>2,
          'Order' => $order,
          'cms_users_id' => $user,
          'created_at' => get_current_time()
      ];
      $id = DB::table('au_log')->insertGetId($dataArray);

      $ret = [
        false,
        $error
      ];
      return json_encode($ret);
  endif;

  return json_encode(false);
	// return $fletes;
});

//We get the credit information from the client
Route::post('/get-credit-info', function (Request $request) {
    $client = $request->id;
  $sql = "select
            a.CardCode,
            a.CardName,
            a.CreditLine,
            a.Balance,
            b.TransType,
            b.Ref1,
            Fecha_Emision = b.RefDate,
            Fecha_Vencimiento = b.DueDate,
            Monto_Original = b.Debit - Credit,
            Monto_Vencido = b.BalDueDeb + b.BalDueCred 
          from
            OCRD a inner join
            JDT1 b on a.CardCode = b.ShortName
          where
            CardCode = '".$client."'
            and b.BalDueCred + b.BalDueDeb > 0";
  $info['balance'] = DB::connection('sqlsrv2')->select($sql);
	$sql = "select ShortName,RefDate fecha,Ref1 documento,BalDueDeb,BalDueCred
            from JDT1
            where ShortName = '".$client."'
            AND BalDueDeb + BalDueCred > 0";
  $info['estado'] = DB::connection('sqlsrv2')->select($sql);
  
	return $info;
});
//Reporte de vendedores
Route::post('/generate-report', function (Request $request) {
  $vendedor = $request->vendedor["value"];
  $inicio = $request->inicio;
  $fin = $request->fin;
  $planta = $request->planta["value"];

  $sql = "SELECT
            p.nombre,
            o.nombre_vendedor,
            o.vendedor,
            d.Producto,
            SUM(d.Cantidad) cantidad
          FROM
            au_order o
          LEFT JOIN au_order_detail d ON
            o.id = d.au_order_id
          LEFT JOIN au_planta p ON
            o.au_planta_id = p.id
          WHERE 
            FechaCarga 
          BETWEEN 
            '".date('Y-m-d',strtotime($inicio))."' 
          AND 
            '".date('Y-m-d',strtotime($fin))."'";
  if(!empty($planta)):
    $sql .= " and o.au_planta_id = ".$planta." ";
  endif;
  if(!empty($vendedor)):
    $sql .= " and o.vendedor = ".$vendedor." ";
  endif;
  $sql .= " 
          GROUP BY
            p.nombre,
            o.nombre_vendedor,
            o.vendedor,
            d.Producto";

  $info = DB::connection('sqlsrv')->select($sql);

  $total = 0;
  foreach($info as $i):
    $total += $i->cantidad;
  endforeach;

  $t = new stdClass();

  $t->nombre="";
  $t->nombre_vendedor="";
  $t->Producto="Total";
  $t->cantidad=$total;
  $info[] = $t;


	return $info;
});


class Login {
    public $DataBaseServer; // string
    public $DataBaseName; // string
    public $DataBaseType; // string
    public $DataBaseUserName; // string
    public $DataBasePassword; // string
    public $CompanyUserName; // string
    public $CompanyPassword; // string
    public $Language; // string
    public $LicenseServer; // string
  }
  
  class LoginResponse {
    public $LoginResult; // string
  }
  
  class LogOut {
    public $sSessionID; // string
  }
  
  class LogOutResponse {
    public $LogOutResult; // string
  }
  
  class GetEmptyQuotationXml {
    public $sSessionID; // string
  }
  
  class GetEmptyQuotationXmlResponse {
    public $GetEmptyQuotationXmlResult; // GetEmptyQuotationXmlResult
  }
  
  class GetEmptyQuotationXmlResult {
    public $any; // <anyXML>
  }
  
  class GetEmptyOrderXml {
    public $sSessionID; // string
  }
  
  class GetEmptyOrderXmlResponse {
    public $GetEmptyOrderXmlResult; // GetEmptyOrderXmlResult
  }
  
  class GetEmptyOrderXmlResult {
    public $any; // <anyXML>
  }
  
  class GetRecordsetXml {
    public $sSessionID; // string
    public $sSQL; // string
  }
  
  class GetRecordsetXmlResponse {
    public $GetRecordsetXmlResult; // GetRecordsetXmlResult
  }
  
  class GetRecordsetXmlResult {
    public $any; // <anyXML>
  }
  
  class AddQuotation {
    public $SessionID; // string
    public $sXmlQuotationObject; // string
  }
  
  class AddQuotationResponse {
    public $AddQuotationResult; // AddQuotationResult
  }
  
  class AddQuotationResult {
    public $any; // <anyXML>
  }
  
  class AddOrder {
    public $SessionID; // string
    public $sXmlOrderObject; // string
  }
  
  class AddOrderResponse {
    public $AddOrderResult; // AddOrderResult
  }
  
  class AddOrderResult {
    public $any; // <anyXML>
  }
  
  class AddPurchaseOrder {
    public $SessionID; // string
    public $sXmlOrderObject; // string
  }
  
  class AddPurchaseOrderResponse {
    public $AddOrderResult; // AddOrderResult
  }
  
  class AddPurchaseOrderResult {
    public $any; // <anyXML>
  }
  
  class UpdateQuotation {
    public $sSessionID; // string
    public $sXmlQuotationObject; // string
  }
  
  class UpdateQuotationResponse {
    public $UpdateQuotationResult; // UpdateQuotationResult
  }
  
  class UpdateQuotationResult {
    public $any; // <anyXML>
  }
  
  class AddOrder_DI {
    public $sSessionID; // string
    public $dsOrder; // dsOrder
  }
  
  class dsOrder {
    public $schema; // <anyXML>
    public $any; // <anyXML>
  }
  
  class AddOrder_DIResponse {
    public $AddOrder_DIResult; // AddOrder_DIResult
  }
  
  class AddOrder_DIResult {
    public $schema; // <anyXML>
    public $any; // <anyXML>
  }
  
  class ConsultaCliente {
    public $sSessionID; // string
    public $sCliente; // string
  }
  
  class ConsultaClienteResponse {
    public $ConsultaClienteResult; // ConsultaClienteResult
  }
  
  class ConsultaClienteResult {
    public $schema; // <anyXML>
    public $any; // <anyXML>
  }
  
  
  /**
   * DiServerServicesSample class
   * 
   *  
   * 
   * @author    Manuel Parra
   * @copyright 2013
   * @package   DiServerBO
   */
  class DiServerServicesSample extends SoapClient {
  
    private static $classmap = array(
                                      'Login' => 'Login',
                                      'LoginResponse' => 'LoginResponse',
                                      'LogOut' => 'LogOut',
                                      'LogOutResponse' => 'LogOutResponse',
                                      'GetEmptyQuotationXml' => 'GetEmptyQuotationXml',
                                      'GetEmptyQuotationXmlResponse' => 'GetEmptyQuotationXmlResponse',
                                      'GetEmptyQuotationXmlResult' => 'GetEmptyQuotationXmlResult',
                                      'GetEmptyOrderXml' => 'GetEmptyOrderXml',
                                      'GetEmptyOrderXmlResponse' => 'GetEmptyOrderXmlResponse',
                                      'GetEmptyOrderXmlResult' => 'GetEmptyOrderXmlResult',
                                      'GetRecordsetXml' => 'GetRecordsetXml',
                                      'GetRecordsetXmlResponse' => 'GetRecordsetXmlResponse',
                                      'GetRecordsetXmlResult' => 'GetRecordsetXmlResult',
                                      'AddQuotation' => 'AddQuotation',
                                      'AddQuotationResponse' => 'AddQuotationResponse',
                                      'AddQuotationResult' => 'AddQuotationResult',
                                      'AddOrder' => 'AddOrder',
                                      'AddPurchaseOrder' => 'AddPurchaseOrder',
                                      'AddOrderResponse' => 'AddOrderResponse',
                                      'AddOrderResult' => 'AddOrderResult',
                                      'UpdateQuotation' => 'UpdateQuotation',
                                      'UpdateQuotationResponse' => 'UpdateQuotationResponse',
                                      'UpdateQuotationResult' => 'UpdateQuotationResult',
                                      'AddOrder_DI' => 'AddOrder_DI',
                                      'dsOrder' => 'dsOrder',
                                      'AddOrder_DIResponse' => 'AddOrder_DIResponse',
                                      'AddOrder_DIResult' => 'AddOrder_DIResult',
                                      'ConsultaCliente' => 'ConsultaCliente',
                                      'ConsultaClienteResponse' => 'ConsultaClienteResponse',
                                      'ConsultaClienteResult' => 'ConsultaClienteResult',
                                     );
  
  // This is the most important thing in this file: WSDL service discover URL.  
  // Use your own URL for WSDL services.
  // Local
  public function __construct($wsdl="http://192.168.1.53/wsSalesQuotation/DiServerServices.asmx?WSDL", $options = array()) {    
  // Texpetrol
  // public function __construct($wsdl="http://192.168.168.9/wsSalesQuotation/DiServerServices.asmx?WSDL", $options = array()) {    
      foreach(self::$classmap as $key => $value) {
                if(!isset($options['classmap'][$key])) {
              $options['classmap'][$key] = $value;
            }
      }
      parent::__construct($wsdl, $options);
    }
  
    /**
     * Login to company 
     *
     * @param Login $parameters
     * @return LoginResponse
     */
    public function Login(Login $parameters) {
      return $this->__soapCall('Login', array($parameters),       array(
              'uri' => 'http://tempuri.org/wsSalesQuotation/Service1',
              'soapaction' => ''
             )
        );
    }
  
    /**
     * LogOut to company 
     *
     * @param LogOut $parameters
     * @return LogOutResponse
     */
    public function LogOut(LogOut $parameters) {
      return $this->__soapCall('LogOut', array($parameters),       array(
              'uri' => 'http://tempuri.org/wsSalesQuotation/Service1',
              'soapaction' => ''
             )
        );
    }
  
    /**
     * Get an XML document of an empty Quotation object 
     *
     * @param GetEmptyQuotationXml $parameters
     * @return GetEmptyQuotationXmlResponse
     */
    public function GetEmptyQuotationXml(GetEmptyQuotationXml $parameters) {
      return $this->__soapCall('GetEmptyQuotationXml', array($parameters),       array(
              'uri' => 'http://tempuri.org/wsSalesQuotation/Service1',
              'soapaction' => ''
             )
        );
    }
  
    /**
     * Get an XML document of an empty Quotation object 
     *
     * @param GetEmptyOrderXml $parameters
     * @return GetEmptyOrderXmlResponse
     */
    public function GetEmptyOrderXml(GetEmptyOrderXml $parameters) {
      return $this->__soapCall('GetEmptyOrderXml', array($parameters),       array(
              'uri' => 'http://tempuri.org/wsSalesQuotation/Service1',
              'soapaction' => ''
             )
        );
    }
  
    /**
     * Get an XML document of an empty Recordset object 
     *
     * @param GetRecordsetXml $parameters
     * @return GetRecordsetXmlResponse
     */
    public function GetRecordsetXml(GetRecordsetXml $parameters) {
      return $this->__soapCall('GetRecordsetXml', array($parameters),       array(
              'uri' => 'http://tempuri.org/wsSalesQuotation/Service1',
              'soapaction' => ''
             )
        );
    }
  
    /**
     * Add Sales Quotation 
     *
     * @param AddQuotation $parameters
     * @return AddQuotationResponse
     */
    public function AddQuotation(AddQuotation $parameters) {
      return $this->__soapCall('AddQuotation', array($parameters),       array(
              'uri' => 'http://tempuri.org/wsSalesQuotation/Service1',
              'soapaction' => ''
             )
        );
    }
  
    /**
     * Add Sales Order 
     *
     * @param AddOrder $parameters
     * @return AddOrderResponse
     */
    public function AddOrder(AddOrder $parameters) {
      return $this->__soapCall('AddOrder', array($parameters),       array(
              'uri' => 'http://tempuri.org/wsSalesQuotation/Service1',
              'soapaction' => ''
             )
        );
    }
  
    /**
     * Add Purchase Order 
     *
     * @param AddPurchaseOrder $parameters
     * @return AddPurchaseOrderResponse
     */
    public function AddPurchaseOrder(AddPurchaseOrder $parameters) {
      var_dump($parameters);
      return $this->__soapCall('AddPurchaseOrder', array($parameters),       array(
              'uri' => 'http://tempuri.org/wsSalesQuotation/Service1',
              'soapaction' => ''
             )
        );
    }
  
    /**
     * Update Quotation object 
     *
     * @param UpdateQuotation $parameters
     * @return UpdateQuotationResponse
     */
    public function UpdateQuotation(UpdateQuotation $parameters) {
      return $this->__soapCall('UpdateQuotation', array($parameters),       array(
              'uri' => 'http://tempuri.org/wsSalesQuotation/Service1',
              'soapaction' => ''
             )
        );
    }
  
    /**
     * Add Sales Order DI 
     *
     * @param AddOrder_DI $parameters
     * @return AddOrder_DIResponse
     */
    public function AddOrder_DI(AddOrder_DI $parameters) {
      return $this->__soapCall('AddOrder_DI', array($parameters),       array(
              'uri' => 'http://tempuri.org/wsSalesQuotation/Service1',
              'soapaction' => ''
             )
        );
    }
  
    /**
     * ConsultaCliente 
     *
     * @param ConsultaCliente $parameters
     * @return ConsultaClienteResponse
     */
    public function ConsultaCliente(ConsultaCliente $parameters) {
      return $this->__soapCall('ConsultaCliente', array($parameters),       array(
              'uri' => 'http://tempuri.org/wsSalesQuotation/Service1',
              'soapaction' => ''
             )
        );
    }
  
  }

############################################################
#Get the current time based on a timezone
function get_current_time(){
	$tz = 'America/Guatemala';
	$timestamp = time();
	$dt = new DateTime("now", new DateTimeZone($tz)); //first argument "must" be a string
	$dt->setTimestamp($timestamp); //adjust the object to correct timestamp
	return $dt->format("Y-m-d H:i:s");
}

############################################################
#Get the current date in the time zone
function get_current_date($FechaCarga){
	$dt = new DateTime($FechaCarga); //first argument "must" be a string
	return $dt->format("Ymd");
}