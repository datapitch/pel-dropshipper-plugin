jQuery(document).ready(function(){
	 jQuery('#edit').click(function(){
	     jQuery('#table').hide();
	     jQuery('#license-second').show();
	 });
});

jQuery(document).ready(function(){
  jQuery(".nav-tabs a").click(function(){
    jQuery(this).tab("show");
  });
  jQuery(".nav-tabs a").on("shown.bs.tab", function(event){
    var x = jQuery(event.target).text();         // active tab
    var y = jQuery(event.relatedTarget).text();  // previous tab
    jQuery(".act span").text(x);
    jQuery(".prev span").text(y);
  });
});


jQuery(document).ready(function(){
	 jQuery('#tab-support').click(function(){
	     jQuery( "#general" ).removeClass( "active in" );
	     jQuery( "#license" ).removeClass( "active in" );
	 });

	 jQuery('#tab-general').click(function(){
	     jQuery( "#support" ).removeClass( "active in" );
	     jQuery( "#license" ).removeClass( "active in" );
	 });

	 jQuery('#tab-license').click(function(){
	     jQuery( "#support" ).removeClass( "active in" );
	     jQuery( "#support" ).removeClass( "active in" );
	 });
});

