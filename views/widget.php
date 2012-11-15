<?php
// @codingStandardsIgnoreFile
$widgetPrefix = 'SHOPBOP_';
$widgetCssPrefix = constant($widgetPrefix . 'WIDGET_CSS');

/** @var $data array */
?>
<!-- Shopbop Widget v<?php echo constant($widgetPrefix.'WIDGET_VERSION');?> -->
<!-- Shopbop Widget PostID: <?php echo $data['postId'];?> -->
<!-- Shopbop Widget Path: <?php echo $data['path'];?> -->
<?php if($data['madeRequest'] === true): ?>
<!-- Shopbop Widget Request Made: TRUE -->
<?php endif; ?>
<div id="<?php echo $widgetCssPrefix; ?>core-widget" class="<?php echo $widgetCssPrefix; ?>core-widget" style="width: <?php echo $data['width']; ?>; max-width: <?php echo $data['max-width']; ?>px">
	<div class="<?php echo $widgetCssPrefix; ?>-widget-wrapper <?php echo strtolower($data['theme']); ?>">

	    <?php // BEGIN MARKETING MESSAGE AND LOGO ?>
		<div class="<?php echo $widgetCssPrefix; ?>widget-header">
			<div class="<?php echo $widgetCssPrefix; ?>widget-marketing-message">
				<?php if(!array_key_exists('mktMsg', $data) || is_null($data['mktMsg']) || trim($data['mktMsg']) == ""): ?>
    				<p class="<?php echo $widgetCssPrefix; ?>widget-marketing-message">
    				    <a href="http://www.shopbop.com/ci/aboutShopBop/returnpolicy.html"
    				       class="<?php echo $widgetCssPrefix; ?>widget-marketing-message-anchor"
    				       target="_blank" rel="nofollow"><?php echo __("Free Shipping Everywhere", constant($widgetPrefix . 'WIDGET_TRANSLATION')); ?></a>
    			    </p>
				<?php else:?>
				    <?php echo $data['mktMsg']; ?>
				<?php endif; ?>
			</div>
			<a href="http://www.shopbop.com/actions/designerindex/viewAllDesigners.action" target="_blank" rel="nofollow">
		        <p class="<?php echo $widgetCssPrefix; ?>widget-logo">Shopbop.com</p>
		    </a>
		</div>
	    <?php // END MARKETING MESSAGE AND LOGO ?>

	    <?php // BEGIN PANE 1 ?>
	    <?php if(count($data['pane1']) > 0): ?>
		<div class="<?php echo $widgetCssPrefix; ?>widget-heading">
			<a href="#"><?php echo __("JUST ARRIVED", constant($widgetPrefix . 'WIDGET_TRANSLATION')); ?></a>
		</div>
		<div class="<?php echo $widgetCssPrefix; ?>widget-panel <?php echo $widgetCssPrefix; ?>widget-carousel">
			<ul>
				<?php foreach($data['pane1'] as $item): ?>
	                <li>
				        <a href="<?php echo $item['url']; ?>" target="_blank" rel="nofollow">
				            <img src="<?php echo $item['image']; ?>" alt="" width="65" height="128" />
						    <span class="<?php echo $widgetCssPrefix; ?>widget-carousel-mask"></span>
				        </a>
                        <div class="<?php echo $widgetCssPrefix; ?>widget-carousel-description">
                        	<div class="<?php echo $widgetCssPrefix; ?>widget-carousel-description-inside <?php echo $widgetCssPrefix; ?>clearfix">
    						<p>
    							<a href="<?php echo (!is_null($item['anchorUrl']) && $item['anchorUrl'] != "") ? $item['anchorUrl'] : $item['url'];  ?>" target="_blank" <?php if($item['hasNoFollow'] == '1'): ?> rel="nofollow"<?php endif; ?>>
                                    <?php if(array_key_exists('anchorText', $item) && $item['anchorText'] != ""): ?>
                                    <?php echo (strlen($item['anchorText']) > (constant($widgetPrefix . 'WIDGET_ANCHOR_TEXT_MAX_LENGTH')-5)) ? substr($item['anchorText'], 0, (constant($widgetPrefix . 'WIDGET_ANCHOR_TEXT_MAX_LENGTH')-5)) . '...' : $item['anchorText']; ?>
                                    <?php else: ?>
								        Shop <?php echo (strlen($item['brand']['name']) > (constant($widgetPrefix . 'WIDGET_ANCHOR_TEXT_MAX_LENGTH')-5)) ? substr($item['brand']['name'], 0, (constant($widgetPrefix . 'WIDGET_ANCHOR_TEXT_MAX_LENGTH')-5)) . '...' : $item['brand']['name']; ?>
                                    <?php endif; ?>
								</a>
    						</p>
    						<a href="<?php echo $item['url']; ?>" target="_blank" rel="nofollow" class="<?php echo $widgetCssPrefix; ?>widget-carousel-description-button-anchor">
    						    <span class="<?php echo $widgetCssPrefix; ?>widget-carousel-button"><?php  echo __("View", constant($widgetPrefix . 'WIDGET_TRANSLATION')); ?></span>
    						</a>
    						</div>
					    </div>
				    </li>
				<?php endforeach; ?>
			</ul>
			<a class="<?php echo $widgetCssPrefix; ?>widget-carousel-arrow <?php echo $widgetCssPrefix; ?>widget-carousel-prev" href="#">&lt;</a>
				<a class="<?php echo $widgetCssPrefix; ?>widget-carousel-arrow <?php echo $widgetCssPrefix; ?>widget-carousel-next" href="#">&gt;</a>
				<a class="<?php echo $widgetCssPrefix; ?>widget-carousel-info" target="_blank"></a>
			<div class="<?php echo $widgetCssPrefix; ?>widget-carousel-pager"></div>
		</div>
		<?php endif; ?>
	    <?php // END PANE 1 ?>

	    <?php // BEGIN PANE 2 ?>
	    <?php if(count($data['pane2']) > 0): ?>
		<div class="<?php echo $widgetCssPrefix; ?>widget-heading">
			<a href="#"><?php echo __($data['pane2Title'], constant($widgetPrefix . 'WIDGET_TRANSLATION')); ?></a>
		</div>
		<div class="<?php echo $widgetCssPrefix; ?>widget-panel <?php echo $widgetCssPrefix; ?>widget-carousel">
			<ul>
			    <?php foreach($data['pane2'] as $item): ?>
				    <li>
    					<a href="<?php echo $item['url']; ?>" target="_blank" rel="nofollow">
    					    <img src="<?php echo $item['image']; ?>" alt="" width="65" height="128" />
    						<span class="<?php echo $widgetCssPrefix; ?>widget-carousel-mask"></span>
    				    </a>
                        <div class="<?php echo $widgetCssPrefix; ?>widget-carousel-description">
                        <div class="<?php echo $widgetCssPrefix; ?>widget-carousel-description-inside <?php echo $widgetCssPrefix; ?>clearfix">
    						<p>
    							<a href="<?php echo (!is_null($item['anchorUrl']) && $item['anchorUrl'] != "") ? $item['anchorUrl'] : $item['url'];  ?>" target="_blank"<?php if($item['hasNoFollow'] == '1'): ?> rel="nofollow"<?php endif; ?>>
                                    <?php if(array_key_exists('anchorText', $item) && $item['anchorText'] != ""): ?>
                                        <?php echo (strlen($item['anchorText']) > (constant($widgetPrefix . 'WIDGET_ANCHOR_TEXT_MAX_LENGTH')-5)) ? substr($item['anchorText'], 0, (constant($widgetPrefix . 'WIDGET_ANCHOR_TEXT_MAX_LENGTH')-5)) . '...' : $item['anchorText']; ?>
                                    <?php else: ?>
									    Shop <?php echo (strlen($item['brand']['name']) > (constant($widgetPrefix . 'WIDGET_ANCHOR_TEXT_MAX_LENGTH')-5)) ? substr($item['brand']['name'], 0, (constant($widgetPrefix . 'WIDGET_ANCHOR_TEXT_MAX_LENGTH')-5)) . '...' : $item['brand']['name']; ?>
                                    <?php endif; ?>
								</a>
    						</p>
    						<a href="<?php echo $item['url'];  ?>" target="_blank" rel="nofollow" class="<?php echo $widgetCssPrefix; ?>widget-carousel-description-button-anchor">
    						    <span class="<?php echo $widgetCssPrefix; ?>widget-carousel-button"><?php  echo __("View", constant($widgetPrefix . 'WIDGET_TRANSLATION')); ?></span>
    						</a>
    					</div>
    					</div>
				    </li>
				<?php endforeach;?>
		    </ul>
			<a class="<?php echo $widgetCssPrefix; ?>widget-carousel-arrow <?php echo $widgetCssPrefix; ?>widget-carousel-prev" href="#">&lt;</a>
			<a class="<?php echo $widgetCssPrefix; ?>widget-carousel-arrow <?php echo $widgetCssPrefix; ?>widget-carousel-next" href="#">&gt;</a>
			<a class="<?php echo $widgetCssPrefix; ?>widget-carousel-info" target="_blank"></a>
			<div class="<?php echo $widgetCssPrefix; ?>widget-carousel-pager"></div>
		</div>
		<?php endif; ?>
	    <?php // END PANE 2 ?>

	    <?php // BEGIN PROMO AREA ?>
		<div class="<?php echo $widgetCssPrefix; ?>widget-heading">
			<a href="#"><?php echo __("FEATURED", constant($widgetPrefix . 'WIDGET_TRANSLATION')); ?></a>
		</div>
	    <?php if(!array_key_exists('promotion', $data) || is_null($data['promotion']) || trim($data['promotion']) == ''): ?>
			<div class="<?php echo $widgetCssPrefix; ?>widget-panel <?php echo $widgetCssPrefix; ?>widget-promotion">
			    <p></p>
		    </div>
		<?php else: ?>
		    <div class="<?php echo $widgetCssPrefix; ?>widget-panel <?php echo $widgetCssPrefix; ?>widget-promotion-defined">
	            <?php echo $data['promotion']; ?>
		    </div>
		<?php endif; ?>
	    <?php // END PROMO AREA ?>

	    <?php // BEGIN GET THIS WIDGET ?>
		<div class="<?php echo $widgetCssPrefix; ?>widget-footer">
			<a href="http://www.shopbop.com/go/widgets" target="_blank" rel="nofollow"><?php echo __('Get this widget', constant($widgetPrefix . 'WIDGET_TRANSLATION')); ?></a>
		</div>
	    <?php // END GET THIS WIDGET ?>

	</div>
</div>
<script type="text/javascript">
jQuery(document).ready(function(){
	var spwTrk = function(e){
    	if(e.which == 1 && !e.metaKey && !e.shiftKey)
    	{
        	var clickTarget = e.target;
        	if(e.target.tagName.toLowerCase() != 'a')
        	{
        		clickTarget = jQuery(clickTarget).parent('a');

            	if(!clickTarget)
            		return true;

            	clickTarget = clickTarget[0];
        	}
    	    var reqParams = {
	    		'href': clickTarget.href,
	    		'noFollow': clickTarget.rel ? (clickTarget.rel.indexOf('nofollow') > -1 ? '1' : '0') : '0',
	    		'page': location.href,
	    		'class': clickTarget.className ? clickTarget.className : '',
	    	    'browserUserAgent': navigator.userAgent ? navigator.userAgent : '--',
	    	    'browserLang': navigator.language ? navigator.language : '--',
	    	    'browserTime': new Date().toString(),
	    	    'element': e.target.tagName.toLowerCase()
    	    };

    	    var img = new Image();
    	    img.src = encodeURI('<?php echo $postUrl; ?>trk?data=' + JSON.stringify(reqParams)).replace('?', '%3F').replace('&', '%26').replace('=', '%3D').replace('/trk%3Fdata%3D', '/trk?data=');
    	    img.width = 1;
    	    img.height = 1;
    	    document.body.appendChild(img);
    	}

    	return true;
    };

    if(jQuery().jquery < "1.7")
    {
    	jQuery('.shopbop-core-widget a').live('click', spwTrk);
    }
    else
    {
    	jQuery('.shopbop-core-widget a').on('click', spwTrk);
    }
});
</script>