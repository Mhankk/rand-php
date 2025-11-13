<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Random String Generator</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .main-card { box-shadow: 0 10px 40px rgba(0,0,0,0.3); border-radius: 15px; }
        .result-box { background: #f8f9fa; border-left: 4px solid #667eea; transition: all 0.3s; }
        .result-box:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .code-text { font-family: 'Courier New', monospace; word-break: break-all; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card main-card">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-2"><i class="fas fa-lock"></i> Random String Generator</h2>
                        <p class="text-center text-muted mb-4">Cryptographically secure password generator</p>

                        <form method="POST" class="mb-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label"><i class="fas fa-ruler"></i> Panjang</label>
                                    <input type="number" class="form-control" name="length" min="4" max="512" value="<?php echo $_POST['length'] ?? 32; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><i class="fas fa-hashtag"></i> Jumlah</label>
                                    <select class="form-select" name="count">
                                        <?php 
                                        $selected = $_POST['count'] ?? 3;
                                        for($i=1; $i<=10; $i++) {
                                            echo "<option value='$i'" . ($selected == $i ? ' selected' : '') . ">$i String</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><i class="fas fa-cog"></i> Charset</label>
                                    <select class="form-select" name="charset">
                                        <?php 
                                        $cs = $_POST['charset'] ?? 'all';
                                        ?>
                                        <option value="all" <?php echo $cs=='all'?'selected':''; ?>>Semua Karakter</option>
                                        <option value="alphanum" <?php echo $cs=='alphanum'?'selected':''; ?>>Alphanumeric</option>
                                        <option value="alpha" <?php echo $cs=='alpha'?'selected':''; ?>>Huruf Saja</option>
                                        <option value="num" <?php echo $cs=='num'?'selected':''; ?>>Angka Saja</option>
                                        <option value="hex" <?php echo $cs=='hex'?'selected':''; ?>>Hexadecimal</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success w-100 mt-3">
                                <i class="fas fa-sync-alt"></i> Generate
                            </button>
                        </form>

                        <?php
                        /**
                         * Generate cryptographically secure random string
                         * Improvements:
                         * - Validasi input yang lebih ketat
                         * - Charset options untuk berbagai kebutuhan
                         * - Error handling yang proper
                         * - Optimasi performa dengan random_bytes untuk hex
                         */
                        class SecureRandomGenerator {
                            private const CHARSETS = [
                                'all' => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+-=[]{}|;:,.<>?',
                                'alphanum' => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
                                'alpha' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
                                'num' => '0123456789',
                                'hex' => '0123456789abcdef'
                            ];

                            /**
                             * Generate random string dengan validasi ketat
                             */
                            public static function generate(int $length, string $charsetType = 'all'): string {
                                // Validasi
                                if ($length < 1 || $length > 512) {
                                    throw new InvalidArgumentException("Length must be between 1 and 512");
                                }

                                if (!isset(self::CHARSETS[$charsetType])) {
                                    throw new InvalidArgumentException("Invalid charset type");
                                }

                                // Optimasi untuk hex: gunakan random_bytes + bin2hex
                                if ($charsetType === 'hex') {
                                    return substr(bin2hex(random_bytes((int)ceil($length / 2))), 0, $length);
                                }

                                // Gunakan random_int untuk charset lain
                                $keyspace = self::CHARSETS[$charsetType];
                                $max = strlen($keyspace) - 1;
                                $result = '';
                                
                                for ($i = 0; $i < $length; $i++) {
                                    $result .= $keyspace[random_int(0, $max)];
                                }
                                
                                return $result;
                            }

                            /**
                             * Calculate entropy (bits)
                             */
                            public static function calculateEntropy(int $length, string $charsetType): float {
                                $charsetSize = strlen(self::CHARSETS[$charsetType]);
                                return $length * log($charsetSize, 2);
                            }

                            /**
                             * Get strength label
                             */
                            public static function getStrength(float $entropy): array {
                                if ($entropy < 60) return ['Weak', 'danger'];
                                if ($entropy < 90) return ['Medium', 'warning'];
                                if ($entropy < 120) return ['Strong', 'success'];
                                return ['Very Strong', 'primary'];
                            }
                        }

                        // Process form
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            try {
                                $length = filter_input(INPUT_POST, 'length', FILTER_VALIDATE_INT, [
                                    'options' => ['min_range' => 1, 'max_range' => 512, 'default' => 32]
                                ]);
                                $count = filter_input(INPUT_POST, 'count', FILTER_VALIDATE_INT, [
                                    'options' => ['min_range' => 1, 'max_range' => 10, 'default' => 3]
                                ]);
                                $charset = $_POST['charset'] ?? 'all';

                                echo '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Generated ' . $count . ' string(s) with length ' . $length . '</div>';

                                for ($i = 0; $i < $count; $i++) {
                                    $randomStr = SecureRandomGenerator::generate($length, $charset);
                                    $entropy = SecureRandomGenerator::calculateEntropy($length, $charset);
                                    [$strengthLabel, $strengthColor] = SecureRandomGenerator::getStrength($entropy);
                                    ?>
                                    <div class="result-box p-3 mb-3 rounded">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <small class="text-muted"><i class="fas fa-key"></i> String #<?php echo $i+1; ?></small>
                                            <button class="btn btn-sm btn-outline-primary copy-btn" data-text="<?php echo htmlspecialchars($randomStr); ?>">
                                                <i class="fas fa-copy"></i> Copy
                                            </button>
                                        </div>
                                        <div class="code-text bg-white p-2 rounded border"><?php echo htmlspecialchars($randomStr); ?></div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <small class="text-muted">Length: <?php echo strlen($randomStr); ?> | Entropy: <?php echo round($entropy, 1); ?> bits</small>
                                            <span class="badge bg-<?php echo $strengthColor; ?>"><?php echo $strengthLabel; ?></span>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                            }
                        }
                        ?>

                        <div class="text-center mt-4">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt"></i> Using PHP random_int() - Cryptographically Secure
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="copyToast" class="toast align-items-center text-white bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body"><i class="fas fa-check"></i> Copied to clipboard!</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const text = this.getAttribute('data-text');
                navigator.clipboard.writeText(text).then(() => {
                    const toast = new bootstrap.Toast(document.getElementById('copyToast'));
                    toast.show();
                });
            });
        });
    </script>
</body>
</html>
