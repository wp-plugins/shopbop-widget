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


            /** Affiliate  window **/
            jQuery('.core-widget-affiliate-wrapper :input[type="radio"]').on('click change', function(e) {
                affiliateChange(jQuery('#core-widget-no-thanks-affiliate').is(':checked'), this.id);
            });


            function affiliateChange(isCheacked, isChanged)
            {
                if(isChanged == 'core-widget-no-thanks-affiliate' || isCheacked)
                {
                    jQuery('#core-widget-affiliate-id').prop('disabled', true);
                }
                else
                {
                    console.log('else')
                    jQuery('#core-widget-affiliate-id').prop('disabled', false);
//                    jQuery('#core-widget-affiliate').val(jQuery('#core-widget-affiliate-id').val());
                }
            }
            affiliateChange(jQuery('#core-widget-no-thanks-affiliate').is(':checked'), true);


		});

