<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PatientProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'condition',
        'last_visit',
        'status',
        'medical_history',
        'allergies',
        'current_medications',
        'family_medical_history',
        'blood_type',
        'height',
        'weight',
        'emergency_contact_name',
        'emergency_contact_phone',
        'smoking_status',
        'alcohol_consumption',
        'lifestyle_notes',
        'next_follow_up',
        'treatment_notes',
        'preferred_language',
        'insurance_provider',
        'insurance_number',
        'insurance_expiry'
    ];

    protected $casts = [
        'last_visit' => 'date',
        'next_follow_up' => 'date',
        'insurance_expiry' => 'date',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getBmiAttribute()
    {
        if ($this->height && $this->weight) {
            $heightInMeters = $this->height / 100;
            return round($this->weight / ($heightInMeters * $heightInMeters), 2);
        }
        return null;
    }

    public function getBmiCategoryAttribute()
    {
        $bmi = $this->bmi;
        if (!$bmi) return null;

        if ($bmi < 18.5) return 'نقص وزن';
        if ($bmi < 25) return 'وزن طبيعي';
        if ($bmi < 30) return 'وزن زائد';
        return 'سمنة';
    }

    public function getAgeAttribute()
    {
        return $this->user->birth_date ? Carbon::parse($this->user->birth_date)->age : null;
    }

    public function getLastVisitFormattedAttribute()
    {
        return $this->last_visit ? $this->last_visit->format('Y-m-d') : null;
    }

    public function getNextFollowUpFormattedAttribute()
    {
        return $this->next_follow_up ? $this->next_follow_up->format('Y-m-d') : null;
    }

    public function getInsuranceExpiryFormattedAttribute()
    {
        return $this->insurance_expiry ? $this->insurance_expiry->format('Y-m-d') : null;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeNeedsFollowUp($query)
    {
        return $query->where('next_follow_up', '<=', Carbon::now()->addDays(30));
    }

    public function scopeWithExpiredInsurance($query)
    {
        return $query->where('insurance_expiry', '<=', Carbon::now());
    }
}
