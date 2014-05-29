
Copy Magento Customers
==============

This is a script that copies a customer from one magento store to another.  The two stores must have the same products.

Contact dan at edenwired dot com with questions/requests, or contact me through github.

Usage
==============

First, configure the source and target databases in config_db.php.

Then, set the id of the customer you want to transfer in magento_alter.php.

Lastly, just run

php ./magento_alter.php


Caveats
==============

This software is distributed with ABSOLUTELY NO WARRANTY!  It may screw up your Magento store - that's your responsibility.

*BACKUP!*

*TEST THIS FIRST WITH A TEST INSTALLATION OF THE TARGET DATABASE*

I don't know why the sales_flat_order table has two unique keys, but if you look for these lines:
				unset( $row['entity_id'] );
				unset( $row['increment_id'] );
you'll see what I'm talking about.

For now, I'm grabbing the max(increment_id)+1 from the target database's sales_flat_order table and using that.  I'm guessing magento has a more sophisticated way to 
