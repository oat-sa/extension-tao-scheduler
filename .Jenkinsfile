pipeline {
    agent {
        label 'builder'
    }
    stages {
        stage('Resolve TAO dependencies') {
            environment {
                GITHUB_ORGANIZATION='oat-sa'
                REPO_NAME='oat-sa/extension-tao-scheduler'
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
                                def b = $BRANCH_NAME
                                writeFile(file: 'composer.json', text: """{
    "require": {
        "oat-sa/extension-tao-devtools" : "dev-TDR-22/feature/dependency_analyzer",
        "oat-sa/extension-tao-scheduler" : "dev-${b}"
    },
    "minimum-stability": "dev",
    "require-dev": {
        "phpunit/phpunit": "~8.5"
    }
}
""")
                            }
                            sh(
                                label: 'Install/Update sources from Composer',
                                script: "COMPOSER_AUTH='{\"github-oauth\": {\"github.com\": \"$GIT_TOKEN\"}}\' composer install --no-interaction --no-ansi --no-progress"
                            )
                            script {
                                deps = sh(returnStdout: true, script: 'php -n taoDevTools\\scripts\\depsInfo.php taoScheduler').trim()
                                deps = deps.substring(deps.indexOf('\n')+1);
                                def propsJson = readJSON text: deps
                                missedDeps = propsJson['missedClasses']['missed'].toString()
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
