pipeline {

    agent { label "!master"} //run on slaves only

    stages {

        stage('initial setup and newtag') {
            steps {
                script {
                    if (env.BRANCH_NAME != "master") {
                        env.STAGEARG = "--stage ci"
                    } else {
                        // this can change to `-dev` tags we we switch over.
                        env.STAGEARG = "--stage master"
                    }
                }
                script {
                    sh '''
                        virtualenv venv
                        . venv/bin/activate
                        pip install git+https://github.com/ministryofjustice/semvertag.git@1.1.0
                        git fetch --tags
                        semvertag bump patch $STAGEARG >> semvertag.txt
                    '''
                }
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
                    docker run -i --rm --user `id -u` -v $(pwd):/app registry.service.opg.digital/opguk/phpcs --standard=PSR2 --report=checkstyle --report-file=checkstyle.xml --runtime-set ignore_warnings_on_exit true --runtime-set ignore_errors_on_exit true module/Application/src/
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
                    docker run -i --rm --user `id -u` -v $(pwd):/app registry.service.opg.digital/opguk/phpunit module/Application/tests -c module/Application/tests/phpunit.xml --log-junit unit_results.xml
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
                    docker run -i --rm --user `id -u` -v $(pwd):/app registry.service.opg.digital/opguk/phpunit module/Application/tests -c module/Application/tests/phpunit.xml --coverage-clover module/Application/tests/coverage/clover.xml --coverage-html module/Application/tests/coverage/
                    echo 'Fixing coverage file paths due to running in container'
                    sed -i "s#<file name=\\"/app#<file name=\\"#" module/Application/tests/coverage/clover.xml
                '''
                step([
                    $class: 'CloverPublisher',
                    cloverReportDir: 'module/Application/tests/coverage',
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
                echo 'No functional tests'
            }
        }

        stage('Build, tag, push image') {
            steps {
                sh '''
                  . venv/bin/activate
                  docker build . -t registry.service.opg.digital/opguk/opg-lpa-api:${NEWTAG}
                  semvertag tag ${NEWTAG}
                  docker push "registry.service.opg.digital/opguk/opg-lpa-api:${NEWTAG}"
                '''
            }
        }

    }
}
