<?php

namespace App\Entity;

use App\Repository\ServiceRepository;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 50)]
    private ?string $phone = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $workDayFrom = null;

    #[ORM\Column(nullable: true)]
    private ?int $workDayTo = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $workTimeFrom = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $workTimeTo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'services')]
    private Collection $categories;

    /**
     * @var Collection<int, Appointment>
     */
    #[ORM\OneToMany(targetEntity: Appointment::class, mappedBy: 'service')]
    private Collection $appointments;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'service')]
    private Collection $employees;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->appointments = new ArrayCollection();
        $this->employees = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getWorkDayFrom(): ?int
    {
        return $this->workDayFrom;
    }

    public function setWorkDayFrom(?int $workDayFrom): static
    {
        $this->workDayFrom = $workDayFrom;

        return $this;
    }

    public function getWorkDayTo(): ?int
    {
        return $this->workDayTo;
    }

    public function setWorkDayTo(?int $workDayTo): static
    {
        $this->workDayTo = $workDayTo;

        return $this;
    }

    public function getWorkTimeFrom(): ?\DateTimeImmutable
    {
        return $this->workTimeFrom;
    }

    public function setWorkTimeFrom(?\DateTimeImmutable $workTimeFrom): static
    {
        $this->workTimeFrom = $workTimeFrom;

        return $this;
    }

    public function getWorkTimeTo(): ?\DateTimeImmutable
    {
        return $this->workTimeTo;
    }

    public function setWorkTimeTo(?\DateTimeImmutable $workTimeTo): static
    {
        $this->workTimeTo = $workTimeTo;

        return $this;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): static
    {
        $this->imageName = $imageName;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(Appointment $appointment): static
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments->add($appointment);
            $appointment->setService($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): static
    {
        if ($this->appointments->removeElement($appointment)) {
            if ($appointment->getService() === $this) {
                $appointment->setService(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getEmployees(): Collection
    {
        return $this->employees;
    }

    public function addEmployee(User $employee): static
    {
        if (!$this->employees->contains($employee)) {
            $this->employees->add($employee);
            $employee->setService($this);
        }

        return $this;
    }

    public function removeEmployee(User $employee): static
    {
        if ($this->employees->removeElement($employee)) {
            if ($employee->getService() === $this) {
                $employee->setService(null);
            }
        }

        return $this;
    }

    public function getWorkDayLabel(?int $day): string
    {
        return match ($day) {
            1 => 'Pirmadienis',
            2 => 'Antradienis',
            3 => 'Trečiadienis',
            4 => 'Ketvirtadienis',
            5 => 'Penktadienis',
            6 => 'Šeštadienis',
            7 => 'Sekmadienis',
            default => 'Nenurodyta',
        };
    }

    public function getFormattedWorkingHours(): string
    {
        if (
            $this->workDayFrom === null ||
            $this->workDayTo === null ||
            $this->workTimeFrom === null ||
            $this->workTimeTo === null
        ) {
            return 'Nenurodytas';
        }

        return sprintf(
            '%s–%s %s–%s',
            $this->getWorkDayLabel($this->workDayFrom),
            $this->getWorkDayLabel($this->workDayTo),
            $this->workTimeFrom->format('H:i'),
            $this->workTimeTo->format('H:i')
        );
    }
}