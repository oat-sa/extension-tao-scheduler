pipeline {
    agent {
        label 'builder'
    }
    parameters {
        string(name: 'branch', defaultValue: '')
    }
    environment {
        REPO_NAME='oat-sa/extension-tao-scheduler'
        EXT_NAME='taoScheduler'
        GITHUB_ORGANIZATION='oat-sa'
    }
    stages {
        stage('Prepare') {
            steps {
                sh(
                    label : 'Create build directory',
                    script: 'mkdir -p build'
                )
                sh(
                    label : 'Create devTools directory',
                    script: 'mkdir -p devTools'
                )
                dir ('devTools') {
                    git branch: "TRD-21/common_jenkins_steps", url: 'https://github.com/oat-sa/extension-tao-devtools.git'
                }
            }
        }
        stage('Install') {
            dir('build') {
                load 'devTools/jenkins/jenkinsInstall'
            }
        }
        stage('Tests') {
            parallel {
                stage('Backend Tests') {
                    agent {
                        docker {
                            image 'alexwijn/docker-git-php-composer'
                            reuseNode true
                        }
                    }
                    options {
                        skipDefaultCheckout()
                    }
                    steps {
                        dir('build'){
                            sh(
                                label: 'Run backend tests',
                                script: "./vendor/bin/phpunit ${EXT_NAME}/test"
                            )
                        }
                    }
                }
            }
        }
        stage('Checks') {
            parallel {
                stage('Backend Checks') {
                    agent {
                        docker {
                            image 'alexwijn/docker-git-php-composer'
                            reuseNode true
                        }
                    }
                    options {
                        skipDefaultCheckout()
                    }
                    steps {
                        dir('build'){
                            script {
                                deps = sh(returnStdout: true, script: "php ./taoDevTools/scripts/depsInfo.php ${EXT_NAME}").trim()
                                echo deps
                                def propsJson = readJSON text: deps
                                missedDeps = propsJson[EXT_NAME]['missedClasses'].toString()
                                try {
                                    assert missedDeps == "[]"
                                } catch(Throwable t) {
                                    error("Missed dependencies found: $missedDeps")
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
