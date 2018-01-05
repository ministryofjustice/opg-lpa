pipeline {

    agent { label "!master"} //run on slaves only

    stages {

        stage('initial setup and newtag') {
            steps {
                script {
                    if (env.BRANCH_NAME != "master") {
                        env.STAGEARG = "--stage dev"
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
                    docker run -i --rm --user `id -u` -v $(pwd):/app registry.service.opg.digital/opg-phpcs-1604 --standard=PSR2 --report=checkstyle --report-file=checkstyle.xml --runtime-set ignore_warnings_on_exit true --runtime-set ignore_errors_on_exit true src/
                '''
            }
            post {
                always {
                    checkstyle pattern: 'checkstyle.xml'
                }
            }
        }

        stage('build') {
            steps {
                sh '''
                    docker-compose down
                    docker-compose build
                    docker-compose run --rm --user `id -u` datamodels bash -c "cd /app;export COMPOSER_HOME='/tmp';composer install"
                '''
            }
        }

        stage('unit tests') {
            steps {
                echo 'PHPUnit'
                sh '''
                    docker-compose run --rm --user `id -u` -w /app datamodels bash -c "vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml --log-junit unit_results.xml"
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
                    docker run -i --rm --user `id -u` -v $(pwd):/app registry.service.opg.digital/opg-phpunit-1604 tests -c tests/phpunit.xml --coverage-clover tests/coverage/clover.xml --coverage-html tests/coverage/
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

        stage('Build, tag and push master image') {
            when {
                branch 'master'
            }
            steps {
                script {
                    sh '''
                    docker build . -t registry.service.opg.digital/opguk/opg-lpa-datamodels
                    docker tag registry.service.opg.digital/opguk/opg-lpa-datamodels \
                        "registry.service.opg.digital/opguk/opg-lpa-datamodels:${NEWTAG}"
                    '''
                }
                script {
                    sh '''
                      . venv/bin/activate
                      docker push registry.service.opg.digital/opguk/opg-lpa-datamodels
                      docker push "registry.service.opg.digital/opguk/opg-lpa-datamodels:${NEWTAG}"
                    '''
                }
            }
        }

        stage('Build, tag and push non-master image') {
            when{
                not {
                    branch 'master'
                }
            }
            steps {
                script {
                    sh '''
                    docker build . -t "registry.service.opg.digital/opguk/opg-lpa-datamodels:${NEWTAG}"
                    '''
                }
                script {
                    sh '''
                      . venv/bin/activate
                      docker push "registry.service.opg.digital/opguk/opg-lpa-datamodels:${NEWTAG}"
                    '''
                }
            }
        }

        //stage('Tag repo with build tag') {
        //    steps {
        //        sh '''
        //          semvertag tag ${NEWTAG}
        //        '''
        //    }
        //}
    }
}