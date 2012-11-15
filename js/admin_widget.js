jQuery(document).ready(
		function() {

			var stickywidget_width_initialValue = jQuery(
					"#widget-width-px").val();
			
			jQuery('.shopbop-add-categories').click(function() {
		        	var container = jQuery('#shopbop-dynamic-category');
		        	containerHtml = container.find('.shopbop-dynamic-category').html();
		        	container.append(containerHtml);
		       });

			jQuery("#slider").slider({
				range : "min",
				value : stickywidget_width_initialValue,
				min : 200,
				max : 350,

				slide : function(event, ui) {
					jQuery("input[name='width-type']").filter('[value=fixed]').attr('checked', true);
					jQuery("#widget-width-px").val(ui.value);
				}
			});
		});

