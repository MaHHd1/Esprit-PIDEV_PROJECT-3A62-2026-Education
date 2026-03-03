<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sessions')]
#[ORM\Index(columns: ['sess_lifetime'], name: 'sessions_sess_lifetime_idx')]
class Session
{
    #[ORM\Id]
    #[ORM\Column(name: 'sess_id', type: 'string', length: 128)]
    private string $sessId;

    #[ORM\Column(
        name: 'sess_data',
        type: 'blob',
        options: ['columnDefinition' => 'BLOB NOT NULL']
    )]
    private $sessData;

    #[ORM\Column(name: 'sess_lifetime', type: 'integer', options: ['unsigned' => true])]
    private int $sessLifetime;

    #[ORM\Column(name: 'sess_time', type: 'integer', options: ['unsigned' => true])]
    private int $sessTime;

    public function getSessId(): string
    {
        return $this->sessId;
    }

    public function setSessId(string $sessId): self
    {
        $this->sessId = $sessId;
        return $this;
    }

    public function getSessData(): string
    {
        if (is_resource($this->sessData)) {
            return stream_get_contents($this->sessData);
        }
        return $this->sessData ?? '';
    }

    public function setSessData($sessData): self
    {
        $this->sessData = $sessData;
        return $this;
    }

    public function getSessLifetime(): int
    {
        return $this->sessLifetime;
    }

    public function setSessLifetime(int $sessLifetime): self
    {
        $this->sessLifetime = $sessLifetime;
        return $this;
    }

    public function getSessTime(): int
    {
        return $this->sessTime;
    }

    public function setSessTime(int $sessTime): self
    {
        $this->sessTime = $sessTime;
        return $this;
    }

    public function isExpired(): bool
    {
        return ($this->sessTime + $this->sessLifetime) < time();
    }

    public function getRemainingLifetime(): int
    {
        $expiryTime = $this->sessTime + $this->sessLifetime;
        $remaining = $expiryTime - time();
        return max(0, $remaining);
    }

    public function getCreatedAt(): \DateTime
    {
        return (new \DateTime())->setTimestamp($this->sessTime);
    }

    public function getExpiresAt(): \DateTime
    {
        return (new \DateTime())->setTimestamp($this->sessTime + $this->sessLifetime);
    }
}