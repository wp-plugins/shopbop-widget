var SWJquery = jQuery;

SWJquery(function($) {
	//alert('iam loaded on public blog');
	
	// CAROUSEL
	var widget_index = 1;
	SWJquery('.shopbop-core-widget .shopbop-widget-carousel').each(function(){
		var root = SWJquery(this);
		
		var totalLinks   = root.find('ul>li').size();
		var visibleLinks = root.find('ul').carouFredSel().triggerHandler("configuration", "items.visible");
		var widgetWidth  = root.width();
		
		SWJquery('.shopbop-core-widget .shopbop-widget-carousel-next').hide();
		SWJquery('.shopbop-core-widget .shopbop-widget-carousel-prev').hide();
		
		var dynamicLinks = Math.ceil((totalLinks/visibleLinks)) * visibleLinks;
		var genLinks 	 = dynamicLinks - totalLinks;
		var cloneLinks   = root.find('ul > li:lt('+ genLinks +')').clone();
		root.find('ul').carouFredSel().append(cloneLinks);
	
		root.attr('id', 'shopbop-widget-' + widget_index);
		root.find('ul').carouFredSel({
			prev : '#shopbop-widget-' + widget_index + ' .shopbop-widget-carousel-prev',
			next : '#shopbop-widget-' + widget_index + ' .shopbop-widget-carousel-next',
			pagination : '#shopbop-widget-' + widget_index + ' .shopbop-widget-carousel-pager',
			circular : false,
			infinite: true,
			auto: false,
			height : 128,
			width: widgetWidth,
			align: "center",
			scroll:
			{
				easing: 'swing'
			},

			debug: false
		});
		
		//Force showing the pager to keep the heights the same
		if(root.find('.shopbop-widget-carousel-pager').hasClass('hidden'))
		{
			root.find('.shopbop-widget-carousel-pager').removeClass('hidden').show();
		}
		
		widget_index++;
	});
	
	//Hides the 
	SWJquery('.shopbop-core-widget').mouseleave(function(){
		
		SWJquery('.shopbop-core-widget .shopbop-widget-carousel-next').hide();
		SWJquery('.shopbop-core-widget .shopbop-widget-carousel-prev').hide();
	});
	
	SWJquery('.shopbop-core-widget').mouseenter(function(){
		
		SWJquery('.shopbop-core-widget .shopbop-widget-carousel-next').fadeIn("slow").show();
		SWJquery('.shopbop-core-widget .shopbop-widget-carousel-prev').fadeIn("slow").show();
	});
	

	

	// SHOW DESCRIPTION FOR ITEMS
	SWJquery('.shopbop-core-widget .shopbop-widget-carousel li').mouseenter(function() {
		var self = $(this);
		self.closest('.shopbop-widget-carousel').find('li').removeClass('hovered').removeClass('not-hovered');
		self.closest('li').addClass('hovered').siblings().addClass('not-hovered');
		self.closest('.shopbop-widget-carousel').find('.shopbop-widget-carousel-info').html(
		self.find('div.shopbop-widget-carousel-description').html()).stop(true, true).delay(500).slideDown();
        if(typeof skimlinks == 'function') {
            var skimlinks_included_classes = ['shopbop-core-widget'];
            skimlinks();
        }
	});
	
	
	var box_keep_open = '.shopbop-core-widget .shopbop-widget-carousel li, .shopbop-core-widget .shopbop-widget-carousel-info';
	SWJquery(box_keep_open).mouseleave(function(e) {
		if(!SWJquery(e.relatedTarget).is(box_keep_open) && SWJquery(e.relatedTarget).closest(box_keep_open).length == 0) {
			$(this).closest('.shopbop-widget-carousel').find('.hovered').removeClass('hovered');
			$(this).closest('.shopbop-widget-carousel').find('.not-hovered').removeClass('not-hovered');
			$(this).closest('.shopbop-widget-carousel').find('.shopbop-widget-carousel-info').slideUp();
			$(this).closest('.shopbop-widget-carousel').find('.shopbop-widget-carousel-info').html(
			$(this).find('div.shopbop-widget-carousel-description').html()).stop(true, true).delay(500).queue(function() {
				$(this).hide();
			});
		}
	});

	SWJquery('.shopbop-widget-carousel-info').hide();

	// ACCORDION MENU
	SWJquery('.shopbop-core-widget').each(function(){
		var self = SWJquery(this);
		self.find('.shopbop-widget-panel').hide();
		self.find('.shopbop-widget-heading:first').addClass('selected').next('.shopbop-widget-panel').slideDown();
		self.find('.shopbop-widget-heading').click(function(e){
			e.preventDefault();
			if($(this).hasClass('selected')) return;
			$(this).closest('.shopbop-core-widget').find('.shopbop-widget-heading').removeClass('selected');
			$(this).closest('.shopbop-core-widget').find('.shopbop-widget-panel').slideUp();
			$(this).addClass('selected').next('.shopbop-widget-panel').slideDown();
		});
		
	});
	
});
