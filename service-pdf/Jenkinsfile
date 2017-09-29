pipeline {

    agent { label "!master"} //run on slaves only

    environment {
        DOCKER_REGISTRY = 'registry.service.opg.digital'
        IMAGE = 'opguk/lpa-pdf'
    }

    stages {

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
                    docker-compose run --rm --user `id -u` pdf bash -c "cd app;php tools/testAll.php"
                '''
            }
            post {
                success {
                    archiveArtifacts artifacts: 'test-data/output/*.pdf'
                }
            }
        }

        stage('create the tag') {
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
                        NEWTAG=$(cat semvertag.txt); semvertag tag ${NEWTAG}
                    '''
                    env.NEWTAG = readFile('semvertag.txt').trim()
                    currentBuild.description = "${IMAGE}:${NEWTAG}"
                }
                echo "Storing ${env.NEWTAG}"
                archiveArtifacts artifacts: 'semvertag.txt'
            }
        }

        stage('build image') {
            steps {
                sh '''
                  docker build . -t ${DOCKER_REGISTRY}/${IMAGE}:${NEWTAG}
                '''
            }
        }

        stage('push image') {
            steps {
                sh '''
                  docker push ${DOCKER_REGISTRY}/${IMAGE}:${NEWTAG}
                '''
            }
        }

        stage('trigger downstream build') {
            when {
                branch 'master'
            }
            steps {
                build job: '/lpa/opg-lpa-docker/master', propagate: false, wait: false
            }
        }
    }

    post {
        // Always cleanup docker containers, especially for aborted jobs.
        always {
            sh '''
              docker-compose down --remove-orphans
            '''
        }
    }

}
