<!-- First you need to extend the CB layout -->
@extends('crudbooster::admin_template')
<!-- Your html goes here -->
@section('content')
<div class='panel panel-default'>
    <div class="panel-body">
        <div class="box-body">
    <?php
    $req = Request::all();
    $event = $req['event'];
    $file = $req['fileToUpload'];
    $path = public_path() . '/uploads/';
    $file->move($path, $file->getClientOriginalName() );
    
    $path = $path.$file->getClientOriginalName();
    $handle = fopen($path, "r");
    $header = true;
    $headerContent = null;

    $i = 0;
    while ($csvLine = fgetcsv($handle, 1000, ",")):
        if ($header):
            $header = false;
            $headerContent = $csvLine;
        else:
            $sql = "SELECT 	*
                    FROM 	au_planta
                    where   nombre like '%".$csvLine[0]."%'";
            $planta = DB::connection('sqlsrv')->select($sql);
            $pid = $planta[0]->id;

            $sql = "";

            $sql = "SELECT 	*
                    FROM 	au_producto
                    where   Nombre like '%".$csvLine[1]."%'";
            $producto = DB::connection('sqlsrv')->select($sql);
            $prid = $producto[0]->id;

            $sql = "select p.au_planta_id pid,r.codigoSAP,p.precio costo, Fecha
					from au_precios p,au_producto r
					where p.au_producto_id=r.id
					and r.id= ".$prid."
                    order by Fecha desc, p.id desc";
			$producto = DB::connection('sqlsrv')->select($sql);

            if(!empty($pid) && !empty($prid)):
                $dataArray = [
                    'au_planta_id' => $pid,
                    'au_producto_id' => $prid,
                    'precio' => $csvLine[2],
                    'Fecha' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s"),
                    'created_at' => date("Y-m-d H:i:s")
                ];
                DB::table('au_precios')->insertGetId($dataArray);

                $sql = "update au_order_detail
                        set Costo = ".$csvLine[2].",
                        PreciosNoOficiales=0
                        where Producto= '".$producto[0]->codigoSAP."'
                        and au_order_id in (
                            select 
                                id
                            from 
                                au_order
                            where 
                                au_order_status_id not in (5,6)
                            and 
                                au_planta_id=".$pid."
                            and 
                                convert(varchar(10), FechaCarga, 120) = '".$producto[0]->Fecha."'
                            )
                            ";
                //echo $sql;
                DB::connection('sqlsrv')->statement($sql);
                
                $i++;
            endif;
        endif;
    endwhile;
    ?>  
        <h1>
            Se ingresaron <?=$i;?> precios.
        </h1>
        </div>
    </div>
</div>
@endsection