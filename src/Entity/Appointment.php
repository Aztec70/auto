<?php

namespace App\Entity;

use App\Repository\AppointmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Service $service = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $visitDate = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $problemDescription = null;

    #[ORM\Column(length: 50)]
    private ?string $status = 'Laukia';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column]
    private ?bool $isSeen = true;

    #[ORM\OneToOne(mappedBy: 'appointment', cascade: ['persist', 'remove'])]
    private ?Report $report = null;

    #[ORM\Column(length: 100)]
    private ?string $carBrand = null;

    #[ORM\Column(length: 100)]
    private ?string $carModel = null;

    #[ORM\Column]
    private ?int $carYear = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $employeeNote = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $cancelReason = null;

    #[ORM\Column]
    private ?bool $needsDescriptionClarification = false;

    #[ORM\Column]
    private ?bool $needsTimeClarification = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $userClarificationNote = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'Laukia';
        $this->isSeen = true;
        $this->needsDescriptionClarification = false;
        $this->needsTimeClarification = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getVisitDate(): ?\DateTimeInterface
    {
        return $this->visitDate;
    }

    public function setVisitDate(\DateTimeInterface $visitDate): static
    {
        $this->visitDate = $visitDate;

        return $this;
    }

    public function getProblemDescription(): ?string
    {
        return $this->problemDescription;
    }

    public function setProblemDescription(string $problemDescription): static
    {
        $this->problemDescription = $problemDescription;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isSeen(): ?bool
    {
        return $this->isSeen;
    }

    public function setIsSeen(bool $isSeen): static
    {
        $this->isSeen = $isSeen;

        return $this;
    }

    public function getReport(): ?Report
    {
        return $this->report;
    }

    public function setReport(?Report $report): static
    {
        $this->report = $report;

        if ($report !== null && $report->getAppointment() !== $this) {
            $report->setAppointment($this);
        }

        return $this;
    }

    public function getCarBrand(): ?string
    {
        return $this->carBrand;
    }

    public function setCarBrand(string $carBrand): static
    {
        $this->carBrand = $carBrand;

        return $this;
    }

    public function getCarModel(): ?string
    {
        return $this->carModel;
    }

    public function setCarModel(string $carModel): static
    {
        $this->carModel = $carModel;

        return $this;
    }

    public function getCarYear(): ?int
    {
        return $this->carYear;
    }

    public function setCarYear(int $carYear): static
    {
        $this->carYear = $carYear;

        return $this;
    }

    public function getEmployeeNote(): ?string
    {
        return $this->employeeNote;
    }

    public function setEmployeeNote(?string $employeeNote): static
    {
        $this->employeeNote = $employeeNote;

        return $this;
    }

    public function getCancelReason(): ?string
    {
        return $this->cancelReason;
    }

    public function setCancelReason(?string $cancelReason): static
    {
        $this->cancelReason = $cancelReason;

        return $this;
    }

    public function isNeedsDescriptionClarification(): ?bool
    {
        return $this->needsDescriptionClarification;
    }

    public function setNeedsDescriptionClarification(bool $needsDescriptionClarification): static
    {
        $this->needsDescriptionClarification = $needsDescriptionClarification;

        return $this;
    }

    public function isNeedsTimeClarification(): ?bool
    {
        return $this->needsTimeClarification;
    }

    public function setNeedsTimeClarification(bool $needsTimeClarification): static
    {
        $this->needsTimeClarification = $needsTimeClarification;

        return $this;
    }

    public function getUserClarificationNote(): ?string
    {
        return $this->userClarificationNote;
    }

    public function setUserClarificationNote(?string $userClarificationNote): static
    {
        $this->userClarificationNote = $userClarificationNote;

        return $this;
    }
}