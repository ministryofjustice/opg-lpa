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
                    docker run -i --rm --user `id -u` -v $(pwd):/app registry.service.opg.digital/opguk/phpcs --standard=PSR2 --report=checkstyle --report-file=checkstyle.xml --runtime-set ignore_warnings_on_exit true --runtime-set ignore_errors_on_exit true src/
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
                    docker run -i --rm --user `id -u` -v $(pwd):/app registry.service.opg.digital/opguk/phpunit tests -c tests/phpunit.xml --log-junit unit_results.xml
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
                    docker run -i --rm --user `id -u` -v $(pwd):/app registry.service.opg.digital/opguk/phpunit tests -c tests/phpunit.xml --coverage-clover tests/coverage/clover.xml --coverage-html tests/coverage/
                    echo 'Fixing coverage file paths due to running in container'
                    sed -i "s#<file name=\\"/app#<file name=\\"#" tests/coverage/clover.xml
                '''
                step([
                    $class: 'CloverPublisher',
                    cloverReportDir: 'tests/coverage',
                    cloverReportFileName: 'clover.xml'
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
                sh '''
                    rm -r -f test-data/output/
                    docker-compose run --rm pdf bash -c "cd app;php tools/testAll.php"
                '''
            }
            post {
                success {
                    archiveArtifacts artifacts: 'test-data/output/*.pdf'
                }
            }
        }

        stage('conditional build') {
            when{
                branch 'master' //Build master branch only
            }
            steps {
                sh '''
                    docker build . -t "registry.service.opg.digital/opguk/opg-lpa-pdf:${NEWTAG}"
                '''
            }
        }

        stage('conditional tag and push') {
            when{
                branch 'master' //Build master branch only
            }
            steps {
                sh '''
                    docker push "registry.service.opg.digital/opguk/opg-lpa-pdf:${NEWTAG}"
                '''
            }
        }
    }
}