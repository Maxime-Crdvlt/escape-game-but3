<?php
/* Client MQTT 3.1.1 minimal, en PHP pur (sockets de base, aucune extension ni bibliothèque).
   Le site et le pont (bridge.php) s'en servent pour parler au broker Mosquitto, sans mosquitto_pub. */

class MqttLite
{
    private $sock = null;
    private string $buf = '';
    private float $lastSend = 0.0;
    private int $packetId = 1;

    public function __construct(
        private string $host = 'localhost',
        private int $port = 1883,
        private string $clientId = 'lerna-php',
        private int $keepAlive = 30,
        private ?array $will = null   // ['topic' => ..., 'payload' => ..., 'retain' => bool]
    ) {}

    public function connected(): bool { return $this->sock !== null; }

    public function connect(float $timeout = 3.0): bool
    {
        $this->close();
        $s = @stream_socket_client("tcp://{$this->host}:{$this->port}", $errno, $errstr, $timeout);
        if (!$s) return false;
        stream_set_timeout($s, (int) ceil($timeout));
        $this->sock = $s;
        $this->buf = '';

        $flags = 0x02; // session propre
        $payload = $this->str($this->clientId);
        if ($this->will) {
            $flags |= 0x04;
            if (!empty($this->will['retain'])) $flags |= 0x20;
            $payload .= $this->str($this->will['topic']) . $this->str($this->will['payload']);
        }
        $vh = "\x00\x04MQTT\x04" . chr($flags) . pack('n', $this->keepAlive);
        if (!$this->send(0x10, $vh . $payload)) { $this->close(); return false; }

        $ack = $this->readExact(4, $timeout);
        if ($ack === null || ord($ack[0]) !== 0x20 || ord($ack[3]) !== 0) { $this->close(); return false; }
        stream_set_blocking($this->sock, false);
        return true;
    }

    public function publish(string $topic, string $payload, bool $retain = false): bool
    {
        if (!$this->sock) return false;
        return $this->send(0x30 | ($retain ? 1 : 0), $this->str($topic) . $payload);
    }

    /** @param string[] $filters  ex. ['lerna/bouton/+', 'lerna/carte/etat'] */
    public function subscribe(array $filters): bool
    {
        if (!$this->sock) return false;
        $id = $this->packetId++;
        $body = pack('n', $id);
        foreach ($filters as $f) $body .= $this->str($f) . "\x00";
        return $this->send(0x82, $body);
    }

    /** Attend jusqu'à $timeoutMs ms et renvoie les messages reçus : [['topic'=>, 'payload'=>, 'retain'=>], ...] */
    public function poll(int $timeoutMs = 200): array
    {
        if (!$this->sock) return [];
        if (microtime(true) - $this->lastSend > $this->keepAlive / 2) $this->send(0xC0, '');
        $r = [$this->sock]; $w = null; $e = null;
        $n = @stream_select($r, $w, $e, intdiv($timeoutMs, 1000), ($timeoutMs % 1000) * 1000);
        if ($n === false) { $this->close(); return []; }
        if ($n > 0) {
            $data = @fread($this->sock, 65536);
            if ($data === false || ($data === '' && feof($this->sock))) { $this->close(); return []; }
            $this->buf .= $data;
        }
        return $this->parse();
    }

    public function disconnect(): void
    {
        if ($this->sock) { $this->send(0xE0, ''); }
        $this->close();
    }

    /** Publie un message et se déconnecte (pratique pour l'API, qui ne reste pas connectée). */
    public static function publishOnce(string $host, int $port, string $topic, string $payload, bool $retain = false): bool
    {
        $c = new self($host, $port, 'lerna-api-' . getmypid() . '-' . mt_rand(1000, 9999), 10);
        if (!$c->connect(2.0)) return false;
        $ok = $c->publish($topic, $payload, $retain);
        $c->disconnect();
        return $ok;
    }

    private function close(): void
    {
        if ($this->sock) { @fclose($this->sock); }
        $this->sock = null;
        $this->buf = '';
    }

    private function str(string $s): string { return pack('n', strlen($s)) . $s; }

    private function send(int $type, string $body): bool
    {
        if (!$this->sock) return false;
        $len = strlen($body);
        $rem = '';
        do {
            $d = $len % 128;
            $len = intdiv($len, 128);
            if ($len > 0) $d |= 128;
            $rem .= chr($d);
        } while ($len > 0);
        $data = chr($type) . $rem . $body;
        $total = strlen($data);
        $written = 0;
        $deadline = microtime(true) + 3.0;
        while ($written < $total) {
            $n = @fwrite($this->sock, substr($data, $written));
            if ($n === false) { $this->close(); return false; }
            if ($n === 0) {
                if (microtime(true) > $deadline) { $this->close(); return false; }
                usleep(2000);
                continue;
            }
            $written += $n;
        }
        $this->lastSend = microtime(true);
        return true;
    }

    private function readExact(int $n, float $timeout): ?string
    {
        $out = '';
        $deadline = microtime(true) + $timeout;
        while (strlen($out) < $n && microtime(true) < $deadline) {
            $chunk = @fread($this->sock, $n - strlen($out));
            if ($chunk === false) return null;
            if ($chunk === '') {
                if (feof($this->sock)) return null;
                usleep(5000);
                continue;
            }
            $out .= $chunk;
        }
        return strlen($out) === $n ? $out : null;
    }

    private function parse(): array
    {
        $out = [];
        while (strlen($this->buf) >= 2) {
            $b0 = ord($this->buf[0]);
            $mul = 1; $len = 0; $i = 1; $ok = false;
            while ($i < strlen($this->buf) && $i <= 4) {
                $d = ord($this->buf[$i++]);
                $len += ($d & 127) * $mul;
                $mul *= 128;
                if (($d & 128) === 0) { $ok = true; break; }
            }
            if (!$ok || strlen($this->buf) < $i + $len) break;
            $body = substr($this->buf, $i, $len);
            $this->buf = substr($this->buf, $i + $len);
            if (($b0 >> 4) === 3) { // PUBLISH
                $qos = ($b0 >> 1) & 3;
                $tl = unpack('n', substr($body, 0, 2))[1];
                $topic = substr($body, 2, $tl);
                $pos = 2 + $tl + ($qos > 0 ? 2 : 0);
                $out[] = ['topic' => $topic, 'payload' => (string) substr($body, $pos), 'retain' => (bool) ($b0 & 1)];
            }
        }
        return $out;
    }
}
