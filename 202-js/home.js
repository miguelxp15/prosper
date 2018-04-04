$(document).ready(function() {
	$.get("/202-account/ajax/alerts.php", function(data) {
		  $( "#tracking202_alerts" ).html(data);
		});

		$.get("/202-account/ajax/tweets.php", function(data) {
		  $( "#tracking202_tweets" ).html(data);
		});

		$.get("/202-account/ajax/posts.php", function(data) {
		  $( "#tracking202_posts" ).html(data);
		});


		$.get("/202-account/ajax/sponsors.php", function(data) {
		  $( "#tracking202_sponsors" ).html(data);
		});
		
});