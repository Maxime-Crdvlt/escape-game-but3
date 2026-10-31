import { Injectable, Logger, OnModuleDestroy, OnModuleInit } from '@nestjs/common';
import { connect, MqttClient } from 'mqtt';

@Injectable()
export class MqttService implements OnModuleInit, OnModuleDestroy {
  private readonly logger = new Logger(MqttService.name);
  private client: MqttClient;

  onModuleInit() {
    const url = process.env.MQTT_URL ?? 'mqtt://localhost:1883';
    this.client = connect(url);

    this.client.on('connect', () => {
      this.logger.log(`Connecté au broker ${url}`);
      this.client.subscribe('lerna/#');
    });

    this.client.on('message', (topic, payload) => {
      this.logger.log(`[${topic}] ${payload.toString()}`);
    });

    this.client.on('error', (err) => this.logger.error(err.message));
  }

  publish(topic: string, message: string) {
    this.client.publish(topic, message);
  }

  onModuleDestroy() {
    this.client.end();
  }
}