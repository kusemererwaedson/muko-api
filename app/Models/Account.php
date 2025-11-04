<?php
// app/Models/Account.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'account_type',
        'provider',
        'account_number',
        'current_balance',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Check if account has sufficient balance for a debit transaction
     */
    public function hasSufficientBalance($amount)
    {
        return $this->current_balance >= $amount;
    }

    /**
     * Update account balance based on transaction
     */
    public function updateBalance($amount, $type)
    {
        if ($type === 'debit') {
            // Debit reduces the balance (expense/withdrawal)
            $this->current_balance -= $amount;
        } else {
            // Credit increases the balance (income/deposit)
            $this->current_balance += $amount;
        }
        
        $this->save();
    }
}