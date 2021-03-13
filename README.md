# discographies
List albums from specific artist's using the Spotify API
 
Before using this server's endpoints, you need to setup your Spotify Client ID & Client Secret in the settings.php file

	// Spotify Settings
	'spotify' => [
		'ClientID' => 'YourClientIDHere',
		'SecretKey' => 'YourClientSecretHere',
	],

---------------------------------------------------------------------------------------
Available endpoints:

1. Albums From Artist [GET]
URL: /v1/albums?q=artistName

This endpoint retrieves all albums from a single artist.
The response body has the next format:

	[
	  {
		"name": "The Joshua Tree (Super Deluxe)",
		"released": "03-03-1987",
		"tracks": 49,
		"cover": {
		  "height": 640,
		  "width": 640,
		  "url": "https://i.scdn.co/image/ab67616d0000b273b7bea3d01f04e6d0408d2afe"
		}
	  }, 
	  [...]
	]

---------------------------------------------------------------------------------------

Have fun!
