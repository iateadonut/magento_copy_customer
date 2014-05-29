<?php

$mg = new magento_alter;



//to copy a customer:
//copy to temp tables
//find next highest or highest + 10 of customer_entity.entity_id of target database
//insert select from for primary keys



class magento_alter
{
	private $pdo_source		= null;
	private $pdo_target		= null;
	
	public $from_id		= null;
	public $to_id		= null;
	
	function __construct()
	{

		include('config_db.php');

		//TURN OFF FOREIGN CONSTRAINTS
		$query = "set foreign_key_checks = 0";
		$this->pdo_source->query($query);		
		
	}
	
	function __destruct()
	{

		//TURN KEY RESTRAINTS BACK ON
		$query = "set foreign_key_checks = 1";
		$this->pdo_source->query($query);		
		
	}

	public function model_alter( $tables )
	{

		foreach ( $tables as $table=>$column )
		{
			echo $table . "\n";
			$query	= "update " . $table . " set " . $column . " = ? where " . $column . " = ?";
			$stmt	= $this->pdo->prepare($query);
			$stmt->execute( array(  $this->to_id, $this->from_id  ) );
			echo $stmt->rowCount() . " rows affected\n";
			if( 00000 != $stmt->errorCode() )
			{
				echo $query . "\n";
				echo "  $this->to_id  $this->from_id \n";
				print_r( $stmt->errorInfo() );
			}
		}
		
	}
	
	public function model_delete( $tables )
	{

		foreach ( $tables as $table=>$column )
		{
			echo $table . "\n";
			$query	= "delete from " . $table . "  where " . $column . " = ?";
			$stmt	= $this->pdo->prepare($query);
			$stmt->execute( array(  $this->to_id  ) );
			echo $stmt->rowCount() . " rows affected\n";
			if( 00000 != $stmt->errorCode() )
			{
				echo $query . "\n";
				echo "  $this->to_id  $this->from_id \n";
				print_r( $stmt->errorInfo() );
			}
		}
		
	}

	/*
	 *FIND ALL TABLES THAT HAVE A FOREIGN KEY RESTRAINT ON customer_entity 
	*/
	public function get_customer_restraint_tables()
	{
		$query = "select table_name, column_name, referenced_table_name, referenced_column_name from information_schema.key_column_usage where referenced_table_name = 'customer_entity';";

		$tables = array();
		foreach( $this->pdo->query($query) as $row )
		{
			$tables[$row['table_name']] = $row['column_name'];
			//echo $row['table_name'] . " " . $row['column_name'] . " " . $row['referenced_table_name'] . " " . $row['referenced_column_name'] . "\n";
			if ( 'entity_id' != $row['referenced_column_name'] || 'customer_entity' != $row['referenced_table_name'] )
			{
				echo 'Line: ' . __LINE__ . ' refers to ' . $row['referenced_table_name'] . '.' . $row['referenced_column_name'] . ' instead of customer_entity.entity_id' . "\n";die();
			}
		}
		return $tables;
		
	}
	
	public function get_customer_id_tables()
	{

		//FIND ALL TABLES WITH THE COLUMN customer_id
		$query = "select table_name, column_name from information_schema.columns where column_name = 'customer_id'";
		$tables2 = array();
		foreach( $this->pdo->query($query) as $row )
		{
			$tables2[$row['table_name']] = $row['column_name'];
		}
		return $tables2;

	}
	
	public function get_primary_keys()
	{

		$query = "select table_name, column_name from information_schema.key_column_usage where CONSTRAINT_NAME = 'PRIMARY'";
		$tables2 = array();
		foreach( $this->pdo->query($query) as $row )
		{
			$tables2[$row['table_name']][] = $row['column_name'];
		}
		return $tables2;
	}
	

	/*
	 * CREATES COMMANDS TO GRAB CUSTOMERS FROM ANOTHER SERVER
	 */
	public function grab_import_customer_commands( array $cids )
	{
		//CREATE COMMANDS TO GRAB DATA FROM OTHER SERVER
		$tables = $this->get_customer_restraint_tables();
		
		$cids2 = '( ' . implode( ',', $cids ) . ' )';
		
		$fields = array_unique ( $tables );
		foreach ( $fields as $field )
		{
			//if ( 'customer_id' != $field )
			//{
				echo $field . "\n";
				
				$tables2 = '';
				foreach ( $tables as $table_name=>$column_name )
				{
					if ( $column_name == $field )
					{
					$tables2 .= $table_name . ' ';
					}
				}
				$command = 'ssh deskliv@198.1.92.2 \'mysqldump deskliv_magento ' . $tables2 . ' --where="' . $field . ' in ' . $cids2 . '"  --no-create-info\' | mysql deskliv_magento';
				echo $command . "\n";
			//}
		}
		
		//NEXT SHOULD FIND ORDERS FROM THIS CUSTOMER AND THEN RUN $this->grab_import_order_commands INSTEAD
		/*
		$tables_customer_id = $this->get_customer_id_tables();
		//print_r($tables2);
		$tables2 = '';
		foreach ( $tables_customer_id as $table_name=>$column_name )
		{
			$tables2 .= $table_name . ' ';
		}
		$command = 'ssh deskliv@198.1.92.2 \'mysqldump deskliv_magento ' . $tables2 . ' --where="customer_id in ' . $cids2 . '" --replace --no-create-info\' | mysql deskliv_magento';
		echo $command . "\n";
		*/
		//exit;

	}

	public function change_cid( $from_id, $to_id )
	{
		
		$this->from_id	= $from_id;
		$this->to_id	= $to_id;
		
		echo "changing customer id from " . $from_id . " to " . $to_id . "\n";

		$tables		= $this->get_customer_restraint_tables();
		$tables2	= $this->get_customer_id_tables();

		$this->model_alter( $tables );
		$this->model_alter( $tables2 );

		//LASTLY, UPDATE THE entity_id COLUMN IN customer_entity
		$query = "update customer_entity set entity_id = ? where entity_id = ?";
		$stmt	= $this->pdo->prepare($query);
		$stmt->execute( array( $this->to_id, $this->from_id ) );
		echo $query."\n";
		//echo "  $this->from_id  $this->to_id \n";
		echo $stmt->rowCount() . " rows affected\n";	
		if( $stmt->errorCode() != 00000 )
		{
			print_r( $stmt->errorInfo() );
		}

	}


	public function delete_cid( $cid )
	{
	
		echo "changing customer id " . $cid . "\n";
	
		$this->to_id = $cid;

		$tables		= $this->get_customer_restraint_tables();
		$tables2	= $this->get_customer_id_tables();

		$this->model_delete( $tables );
		$this->model_delete( $tables2 );

		//LASTLY, DELETE THE entity_id COLUMN IN customer_entity
		$query = "delete from customer_entity where entity_id = ?";
		$stmt	= $this->pdo->prepare($query);
		$stmt->execute( array( $this->to_id ) );
		echo $query."\n";
		//echo "  $this->from_id  $this->to_id \n";
		echo $stmt->rowCount() . " rows affected\n";	
		if( $stmt->errorCode() != 00000 )
		{
			print_r( $stmt->errorInfo() );
		}
	}


	//CHANGE PRIMARY KEYS OF ALL SALES RELATED TABLES
	public function sales_change_primary_keys( $add_increment = 10000 )
	{
		$tables = $this->get_sales_restraint_tables();
		
		$primary_keys = $this->get_primary_keys();
		
		//$orders = '( ' . implode( ',', $order_ids) . ' )';
		
		
		$fields = array_unique ( $tables );
		foreach ( $fields as $field )
		{


			echo $field . "--\n";
			
			$tables2 = '';
			foreach ( $tables as $table_name=>$column_name )
			{
				if ( $column_name == $field )
				{
					//echo $table_name . "\n";
					//DON'T TOUCH IF TWO PRIMARY KEYS
					if ( count( $primary_keys[$table_name] ) > 1 )
					{
						$result = $this->pdo->query('select count(*) from ' . $table_name . ' ')->fetchColumn();
						echo $table_name . ' has two primary keys and ' . $result . ' rows' . "\n";
						if ( $result != 0 ) { exit; }
						
					} else {
						
						//IF THE PRIMARY KEY IS THE SAME AS THE RESTRAINING KEY, DON'T TOUCH
						if ( $primary_keys[$table_name][0] == $field )
						{
							
						} else {
							echo $table_name . ' ' . $primary_keys[$table_name][0] . ' ' . $field . "\n";
							$this->pdo->query('update ' . $table_name . ' set ' . $primary_keys[$table_name][0] . ' = ' . $primary_keys[$table_name][0] . ' + ' . $add_increment );
					
						}
					}
				}
			}
		}		
	}



	//CHANGE PRIMARY KEYS OF ALL SALES RELATED TABLES
	public function customers_change_primary_keys( $add_increment = 1000 )
	{
		$tables = $this->get_customer_restraint_tables();
		
		$primary_keys = $this->get_primary_keys();
		
		//$orders = '( ' . implode( ',', $order_ids) . ' )';
		
		
		$fields = array_unique ( $tables );
		foreach ( $fields as $field )
		{

			echo $field . "--\n";
			
			$tables2 = '';
			foreach ( $tables as $table_name=>$column_name )
			{
				if ( $column_name == $field )
				{
					//echo $table_name . "\n";
					//DON'T TOUCH IF TWO PRIMARY KEYS
					if ( count( $primary_keys[$table_name] ) > 1 )
					{
						$result = $this->pdo->query('select count(*) from ' . $table_name . ' ')->fetchColumn();
						echo $table_name . ' has two primary keys and ' . $result . ' rows' . "\n";
						//if ( $result != 0 ) { exit; }
						
					} else {
						
						//IF THE PRIMARY KEY IS THE SAME AS THE RESTRAINING KEY, DON'T TOUCH
						if ( $primary_keys[$table_name][0] == $field )
						{
							
						} else {
							echo $table_name . ' ' . $primary_keys[$table_name][0] . ' ' . $field . "\n";
							$query = 'select max(' . $primary_keys[$table_name][0] . ') from ' . $table_name;
							$max_id = $this->pdo->query($query)->fetchColumn();
							
							$query = 'update ' . $table_name . ' set ' . $primary_keys[$table_name][0] . ' = ' . $max_id . ' + ' . $add_increment;
							echo $query . "\n";
							$this->pdo->query( $query );
					
						}
					}
				}
			}
		}		
	}




	public function grab_import_sales_commands( array $order_ids )
	{
		//CREATE COMMANDS TO GRAB DATA FROM OTHER SERVER
		$tables = $this->get_sales_restraint_tables();
		
		$orders = '( ' . implode( ',', $order_ids) . ' )';
		
		$fields = array_unique ( $tables );
		foreach ( $fields as $field )
		{
			//if ( 'order_id' != $field )
			//{
				echo $field . "\n";
				
				$tables2 = '';
				foreach ( $tables as $table_name=>$column_name )
				{
					if ( $column_name == $field )
					{
					$tables2 .= $table_name . ' ';
					}
				}
				$command = 'ssh deskliv@198.1.92.2 \'mysqldump deskliv_magento ' . $tables2 . ' --where="' . $field . ' in  ' . $orders . ' "  --no-create-info\' | mysql deskliv_magento';
				echo $command . "\n";
			//}
		}
		
		/*
		$tables_order_id = $this->get_order_id_tables();
		//print_r($tables2);
		$tables2 = '';
		foreach ( $tables_order_id as $table_name=>$column_name )
		{
			$tables2 .= $table_name . ' ';
		}
		$command = 'ssh deskliv@198.1.92.2 \'mysqldump deskliv_magento ' . $tables2 . ' --where="order_id in  ' . $orders . ' " --replace --no-create-info\' | mysql deskliv_magento';
		echo "order_id\n";
		echo $command . "\n";
		*/
		//exit;

	}

	public function get_sales_restraint_tables()
	{
		$query = "select table_name, column_name, referenced_table_name, referenced_column_name from information_schema.key_column_usage where referenced_table_name = 'sales_flat_order';";

		$tables = array();
		foreach( $this->pdo->query($query) as $row )
		{
			$tables[$row['table_name']] = $row['column_name'];
			if ( 'entity_id' != $row['referenced_column_name'] || 'sales_flat_order' != $row['referenced_table_name'] )
			{
				echo 'Line: ' . __LINE__ . ' refers to ' . $row['referenced_table_name'] . '.' . $row['referenced_column_name'] . ' instead of customer_entity.entity_id' . "\n";die();
			}
		}
		return $tables;
		
	}
	
	public function get_order_id_tables()
	{

		$tables2 = array();
		//FIND ALL TABLES WITH THE COLUMN customer_id
		$query = "select table_name, column_name from information_schema.columns where column_name = 'order_id'";
		$tables2 = array();
		foreach( $this->pdo->query($query) as $row )
		{
			$tables2[$row['table_name']] = $row['column_name'];
		}
		return $tables2;

	}



	public function change_sales_flat_order_id( $from_id, $to_id )
	{
		
		$this->from_id	= $from_id;
		$this->to_id	= $to_id;
		
		echo "changing customer id from " . $from_id . " to " . $to_id . "\n";

		$tables = $this->get_sales_restraint_tables();
		$tables2 = $this->get_order_id_tables();
		
		echo count($tables);
		print_r($tables);
		echo count($tables2);
		print_r($tables2);
		exit;

		exit;
		//print_r($tables);exit;
		$this->model_alter( $tables );
		//$this->model_alter( $tables2 );

		//LASTLY, UPDATE THE entity_id COLUMN IN sales_flat_order
		$query = "update customer_entity set entity_id = ? where entity_id = ?";
		$stmt	= $this->pdo->prepare($query);
		$stmt->execute( array( $this->to_id, $this->from_id ) );		

	}

}




