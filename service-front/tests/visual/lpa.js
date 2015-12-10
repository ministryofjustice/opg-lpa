// Setup

var baseDomain = '192.168.33.103/'; // Local
//var baseDomain = 'front2-staging.lpa.opg.digital'; // Staging
var basePath = 'https://' + baseDomain;

var paths = {};
paths.home = 'home';
paths.login = 'login';
paths.passwordReset = 'forgot-password';

/*
	Require and initialise PhantomCSS module
	Paths are relative to CasperJs directory
*/

var fs = require( 'fs' );
var phantomcss = require( fs.absolute( fs.workingDirectory + '/phantomcss.js' ) );

casper.test.begin( 'LPA tests', function ( test ) {

	phantomcss.init( {
		rebase: casper.cli.get( "rebase" ),
		// SlimerJS needs explicit knowledge of this Casper, and lots of absolute paths
		casper: casper,
		libraryRoot: fs.absolute( fs.workingDirectory + '' ),
		screenshotRoot: fs.absolute( fs.workingDirectory + '/screenshots' ),
		failedComparisonsRoot: fs.absolute( fs.workingDirectory + '/failures' ),
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
	casper.start();

	//casper.setHttpAuth('opg', '4UDt8Wx1j7imsFX'); // Required for staging

	// Desktop Screenshots (1024)
	var screensize = 'desktop';
	casper.then(function(){
		casper.viewport(1024,768);
	});
	casper.thenOpen(basePath + paths.home, function(){
		phantomcss.screenshot( '#global-header', 'header_' + screensize );
		phantomcss.screenshot( '#footer', 'footer_' + screensize );
		phantomcss.screenshot( '#content', paths.home + '_content_' + screensize );
	});

	casper.thenOpen(basePath + paths.login, function(){
		phantomcss.screenshot( '#content', paths.login + '_content_' + screensize );
	});

	casper.thenOpen(basePath + paths.passwordReset, function(){
		phantomcss.screenshot( '#content', paths.passwordReset + '_content_' + screensize );
	});

	// Mobile Screenshots (380)
	var screensize = 'mobile';
	casper.then(function(){
		casper.viewport(380,600);
	});
	casper.thenOpen(basePath + paths.home, function(){
		phantomcss.screenshot( '#global-header', 'header_' + screensize );
		phantomcss.screenshot( '#footer', 'footer_' + screensize );
		phantomcss.screenshot( '#content', paths.home + '_content_' + screensize );
	});

	casper.thenOpen(basePath + paths.login, function(){
		phantomcss.screenshot( '#content', paths.login + '_content_' + screensize );
	});

	casper.thenOpen(basePath + paths.passwordReset, function(){
		phantomcss.screenshot( '#content', paths.passwordReset + '_content_' + screensize );
	});


	// Compare
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
