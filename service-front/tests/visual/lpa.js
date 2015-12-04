/*
	Require and initialise PhantomCSS module
	Paths are relative to CasperJs directory
*/

var fs = require( 'fs' );
var path = fs.absolute( fs.workingDirectory + '/phantomcss.js' );
var phantomcss = require( path );

casper.test.begin( 'LPA tests', function ( test ) {

	phantomcss.init( {
		rebase: casper.cli.get( "rebase" ),
		// SlimerJS needs explicit knowledge of this Casper, and lots of absolute paths
		casper: casper,
		libraryRoot: fs.absolute( fs.workingDirectory + '' ),
		screenshotRoot: fs.absolute( fs.workingDirectory + '/screenshots' ),
		failedComparisonsRoot: fs.absolute( fs.workingDirectory + '/demo/failures' ),
		addLabelToFailedImage: false,
		/*
		screenshotRoot: '/screenshots',
		failedComparisonsRoot: '/failures'
		casper: specific_instance_of_casper,
		libraryRoot: '/phantomcss',
		fileNameGetter: function overide_file_naming(){},
		onPass: function passCallback(){},
		onFail: function failCallback(){},
		onTimeout: function timeoutCallback(){},
		onComplete: function completeCallback(){},
		hideElements: '#thing.selector',
		addLabelToFailedImage: true,
		outputSettings: {
			errorColor: {
				red: 255,
				green: 255,
				blue: 0
			},
			errorType: 'movement',
			transparency: 0.3
		}*/
	} );

	casper.on( 'remote.message', function ( msg ) {
		//this.echo( msg );
	} )

	casper.on( 'error', function ( err ) {
		this.die( "PhantomJS has errored: " + err );
	} );

	casper.on( 'resource.error', function ( err ) {
		casper.log( 'Resource load error: ' + err, 'warning' );
	} );
	/*
		The test scenario
	*/
	var testUrl = 'https://192.168.33.103/home';
	//casper.start('https://frontv2-staging.lpa.opg.digital/home'); // staging
	casper.start(testUrl); // local 

	//asper.setHttpAuth('opg', '4UDt8Wx1j7imsFX');

	casper.then(function(){
		casper.viewport(1024,768)
	});
	casper.thenOpen(testUrl, function(){
		casper.wait(5000);
	});

	casper.then( function () {
		phantomcss.screenshot( '#global-header', 'desktop home header' );
		phantomcss.screenshot( '#footer', 'desktop home footer' );
	} );

	casper.then( function now_check_the_screenshots() {
		phantomcss.compareAll();
		// compare screenshots
	} );


	casper.then(function(){
		casper.viewport(380,600)
	});
	casper.thenOpen(testUrl, function(){
		casper.wait(5000);
	});

	casper.then( function () {
		phantomcss.screenshot( '#global-header', 'mobile home header' );
		phantomcss.screenshot( '#footer', 'mobile home footer' );
	} );

	casper.then( function now_check_the_screenshots() {
		phantomcss.compareAll();
		// compare screenshots
	} );



	/*
	Casper runs tests
	*/
	casper.run( function () {
		console.log( '\nTHE END.' );
		// phantomcss.getExitStatus() // pass or fail?
		casper.test.done();
	} );
} );
