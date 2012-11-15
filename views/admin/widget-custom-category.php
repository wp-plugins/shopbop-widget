<?php if(!$noDiv): ?>
<div id='shopbop-dynamic-category'>
    <table>
<?php endif; ?>
        <?php foreach($wpCategories as $wpCategory): ?>
        	<tr>
        	    <td style="padding-left:<?php echo $indent*20; ?>px">
        	        <b><?php echo $wpCategory->cat_name; ?></b>
        	        <input type='hidden' name='wpCategory[]' value="<?php echo $wpCategory->cat_ID; ?>" />
                </td>
                <td>
                    <select name='apiCategories[<?php echo $wpCategory->cat_ID; ?>]' style='min-width: 150px'>
                        <?php echo $options[$wpCategory->cat_ID]; ?>
                    </select>
                </td>
            </tr>
            <?php if(count($wpCategory->children)>0): ?>
            <?php
		    	$params = array(
		               'wpCategories' => $wpCategory->children,
		               'options'      => $options,
		    		   'noDiv'      => true,
		    		   'indent'     => $indent+1,
		              );
	            
	            $widgetView = new CoreWidgetBase($this->widgetPrefix);
	            $widgetView->loadView('admin/widget-custom-category', $params);            
            ?>
            <?php endif; ?>
        <?php endforeach; ?>
<?php if(!$noDiv): ?>
        </table>
</div>
<?php endif; ?>
