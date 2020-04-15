pipeline {
    agent {
        label 'builder'
    }
    stages {
        stage('Prepare') {
            environment {
                GITHUB_ORGANIZATION='oat-sa'
                REPO_NAME='oat-sa/extension-tao-scheduler'
                EXT_NAME='taoScheduler'
            }
            steps {
                sh(
                    label : 'Create build build directory',
                    script: 'mkdir -p build'
                )
            }
        }
        stage('Install') {
            agent {
                docker {
                    image 'alexwijn/docker-git-php-composer'
                    reuseNode true
                }
            }
            environment {
                HOME = '.'
            }
            options {
                skipDefaultCheckout()
            }
            steps {
                dir('build') {
                    script {
                        def b
                        if (env.CHANGE_BRANCH != null) {
                            b = CHANGE_BRANCH
                        } else {
                            b = BRANCH_NAME
                        }
                        echo b
                        writeFile(file: 'composer.json', text: """
                        {
                            "require": {
                                "oat-sa/extension-tao-devtools" : "dev-TDR-22/feature/dependency_analyzer",
                                "${REPO_NAME}" : "dev-${b}"
                            },
                            "minimum-stability": "dev",
                            "require-dev": {
                                "phpunit/phpunit": "~8.5"
                            }
                        }
                        """
                       )
                    }
                    withCredentials([string(credentialsId: 'jenkins_github_token', variable: 'GIT_TOKEN')]) {
                        sh(
                            label: 'Install/Update sources from Composer',
                            script: "COMPOSER_AUTH='{\"github-oauth\": {\"github.com\": \"$GIT_TOKEN\"}}\' composer update --no-interaction --no-ansi --no-progress"
                        )
                    }
                }
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
                                //deps = deps.substring(deps.indexOf('\n')+1);
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
