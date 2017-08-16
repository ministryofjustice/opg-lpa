pipeline {

    agent { label "!master"} //run on slaves only

    stages {
        stage('lint') {
            steps {
                echo 'PHP_CodeSniffer PSR-2'
                sh '''
                    make cs
                '''
            }
        }

        stage('unit tests') {
            steps {
                echo 'PHPUnit'
                sh '''
                    make test
                '''
            }
        }

        stage('unit tests coverage') {
            steps {
                echo 'PHPUnit with coverage'
                sh '''
                    make testcoverage
                '''
            }
        }

        stage('build') {
            steps {
                echo 'docker-compose build'
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
                echo 'docker build'
            }
        }

        stage('conditional tag and push') {
            when{
                branch 'master' //Build master branch only
            }
            steps {
                echo 'docker push'
            }
        }
    }
}