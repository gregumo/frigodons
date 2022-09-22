<?php

namespace App\Repository;

use App\Entity\User;

interface DateInterface
{
    public function getDay(): ?\DateTimeInterface;

    public function getUser(): ?User;

    public function setUser(?User $user): self;
}
