pipeline {

    agent { label "!master"} //run on slaves only

    stages {

        stage('initial setup and newtag') {
            steps {
                sh '''
                    virtualenv venv
                    . venv/bin/activate
                    pip install git+https://github.com/ministryofjustice/semvertag.git@1.1.0
                    git fetch --tags
                    semvertag bump patch >> semvertag.txt
                '''
            script {
                env.NEWTAG = readFile('semvertag.txt').trim()
            }
                echo "NEWTAG will be ${env.NEWTAG}"
            }
        }

        stage('lint') {
            steps {
                echo 'PHP_CodeSniffer PSR-2'
                sh '''
                    docker run -i --rm -v $(pwd):/app registry.service.opg.digital/opguk/phpcs --standard=PSR2 --report=checkstyle --report-file=checkstyle.xml --runtime-set ignore_warnings_on_exit true --runtime-set ignore_errors_on_exit true module/Application/src/
                '''
            }
            post {
                always {
                    checkstyle pattern: 'checkstyle.xml'
                }
            }
        }

        stage('unit tests') {
            steps {
                echo 'PHPUnit'
                sh '''
                    docker run -i --rm -v $(pwd):/app registry.service.opg.digital/opguk/phpunit module/Application/tests -c module/Application/tests/phpunit.xml --log-junit unit_results.xml
                '''
            }
            post {
                always {
                    junit 'unit_results.xml'
                }
            }
        }

        stage('unit tests coverage') {
            steps {
                echo 'PHPUnit with coverage'
                sh '''
                    docker run -i --rm -v $(pwd):/app registry.service.opg.digital/opguk/phpunit module/Application/tests -c module/Application/tests/phpunit.xml --coverage-clover unit_coverage.xml
                    echo 'Fixing coverage file paths due to running in container'
                    sed -i "s#<file name=\\"/app#<file name=\\"$(pwd)#" unit_coverage.xml
                '''
                step([
                    $class: 'CloverPublisher',
                    cloverReportDir: '',
                    cloverReportFileName: 'unit_coverage.xml'
                ])
            }
        }

        stage('build') {
            steps {
                sh '''
                    docker-compose down
                    docker-compose build
                '''
            }
        }

        stage('functional tests') {
            steps {
                echo 'No functional tests'
            }
        }

        stage('conditional build') {
            when{
                branch 'master' //Build master branch only
            }
            steps {
                sh '''
                    docker build . -t "registry.service.opg.digital/opguk/opg-lpa-front:${NEWTAG}"
                '''
            }
        }

        stage('conditional tag and push') {
            when{
                branch 'master' //Build master branch only
            }
            steps {
                sh '''
                    docker push "registry.service.opg.digital/opguk/opg-lpa-front:${NEWTAG}"
                '''
            }
        }
    }
}